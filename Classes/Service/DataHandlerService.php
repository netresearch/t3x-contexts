<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Api\Configuration;
use PDO;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function count;
use function is_array;

/**
 * Class for TCEmain-hooks: Capture incoming default and record settings
 * and save them to the settings table and the enabled fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class DataHandlerService
{
    /**
     * @var array<int|string, array<string, string>>
     */
    protected array $currentSettings = [];

    /**
     * Extract the context settings from the field array and set them in
     * currentSettings. This function is called by TYPO each time a record
     * is saved in the backend.
     *
     * @param array       &$incomingFieldArray
     * @param string       $table
     * @param string       $id
     * @param DataHandler &$reference
     *
     * @return void
     */
// @codingStandardsIgnoreStart
    public function processDatamap_preProcessFieldArray(
// @codingStandardsIgnoreEnd
        array &$incomingFieldArray,
        string $table,
        string $id,
        DataHandler $reference
    ): void {
        if (
            $table === 'tx_contexts_contexts'
            && isset($incomingFieldArray['default_settings'])
            && is_array($incomingFieldArray['default_settings'])
        ) {
            $this->currentSettings = $incomingFieldArray['default_settings'];
            unset($incomingFieldArray['default_settings']);
            return;
        }

        if (isset($incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN])) {
            $this->currentSettings = $incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN];
            unset($incomingFieldArray[Configuration::RECORD_SETTINGS_COLUMN]);
        }
    }

    /**
     * Finally save the settings
     *
     * @param string      $status
     * @param string      $table
     * @param string      $id
     * @param array       $fieldArray
     * @param DataHandler $reference
     *
     * @return void
     *
     * @throws DBALException
     * @throws Exception
     */
// @codingStandardsIgnoreStart
    public function processDatamap_afterDatabaseOperations(
// @codingStandardsIgnoreEnd
        string $status,
        string $table,
        string $id,
        array $fieldArray,
        DataHandler $reference
    ): void {
        if (count($this->currentSettings) > 0) {
            if (!is_numeric($id)) {
                $id = $reference->substNEWwithIDs[$id];
            }

            if ($table === 'tx_contexts_contexts') {
                $this->saveDefaultSettings((int) $id, $this->currentSettings);
            } else {
                $this->saveRecordSettings($table, (int) $id, $this->currentSettings);
                $this->saveFlatSettings($table, (int) $id, $this->currentSettings);
            }

            unset($this->currentSettings);
        }
    }

    /**
     * Save the settings for a specific record: For each context and field
     * there will be a setting record if the setting is Yes or No. If its
     * blank (n/a) eventually existing records will be deleted.
     *
     * @param string $table
     * @param int    $uid
     * @param array  $contextsAndSettings
     *
     * @return void
     *
     * @throws DBALException
     * @throws Exception
     */
    protected function saveRecordSettings(string $table, int $uid, array $contextsAndSettings): void
    {
        $flatSettingColumns = Configuration::getFlatColumns($table);
        $connectionPool     = GeneralUtility::makeInstance(ConnectionPool::class);

        foreach ($contextsAndSettings as $contextId => $settings) {
            foreach ($settings as $field => $setting) {
                if (isset($flatSettingColumns[$field])) {
                    continue;
                }
                $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_contexts_contexts');
                $row = $queryBuilder->select('uid')
                    ->from('tx_contexts_settings')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'context_uid',
                            $queryBuilder->createNamedParameter((int)$contextId, PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'foreign_table',
                            $queryBuilder->createNamedParameter($table)
                        ),
                        $queryBuilder->expr()->eq(
                            'name',
                            $queryBuilder->createNamedParameter('tx_contexts_settings')
                        ),
                        $queryBuilder->expr()->in(
                            'foreign_uid',
                            $queryBuilder->createNamedParameter($uid, PDO::PARAM_INT)
                        )
                    )
                    ->execute()
                    ->fetchAssociative();
                $connection = $connectionPool->getConnectionForTable('tx_contexts_settings');
                if ((int)$setting === 0 || (int)$setting === 1) {
                    if ($row) {
                        $connection->update(
                            'tx_contexts_settings',
                            ['enabled' => (int)$settings],
                            ['uid' => (int)$row['uid']],
                            [PDO::PARAM_INT]
                        );
                    } else {
                        $connection->insert(
                            'tx_contexts_settings',
                            [
                                'context_uid' => (int)$contextId,
                                'foreign_table' => $table,
                                'name' => $field,
                                'foreign_uid' => $uid,
                                'enabled' => (int)$setting
                            ],
                            [
                                PDO::PARAM_INT,
                                PDO::PARAM_STR,
                                PDO::PARAM_STR,
                                PDO::PARAM_INT,
                                PDO::PARAM_INT
                            ]
                        );
                    }
                } elseif ($row) {
                    $connection->delete(
                        'tx_contexts_settings',
                        ['uid' => (int)$row['uid']],
                        [PDO::PARAM_INT]
                    );
                }
            }
        }
    }

    /**
     * Saves the settings which were configured to be flattened into theyr flat
     * columns on the table to allow quicker queries in enableField hook and to
     * save queries for already fetched rows
     * hook.
     *
     * @param string $table
     * @param int    $uid
     * @param array  $contextsAndSettings Array of settings.
     *                                    Key is the context UID.
     *                                    Value is an array of setting names
     *                                    and their value, e.g.
     *                                    tx_contexts_visibility => '',
     *                                    menu_visibility => '0'
     *                                    '' = undecided, 1 - on, 0 - off
     *
     * @return void
     * @see FrontendControllerService::enableFields()
     */
    protected function saveFlatSettings(string $table, int $uid, array $contextsAndSettings): void
    {
        $values = [];

        $flatSettingColumns = Configuration::getFlatColumns($table);

        foreach ($flatSettingColumns as $setting => $flatColumns) {
            $values[$flatColumns[0]] = [];
            $values[$flatColumns[1]] = [];
            foreach ($contextsAndSettings as $contextId => $settings) {
                if ($settings[$setting] === '0' || $settings[$setting] === '1') {
                    $values[$flatColumns[$settings[$setting]]][] = $contextId;
                }
            }
        }

        if (count($values) > 0) {
            foreach ($values as $colname => &$val) {
                $val = implode(',', $val);
            }

            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table);

            $connection->update(
                $table,
                $values,
                ['uid' => $uid]
            );
        }
    }

    /**
     * Save the default settings to the settings table - default
     * settings will have a foreign_uid of 0
     *
     * @param int   $contextId
     * @param array $settings
     *
     * @return void
     *
     * @throws DBALException
     * @throws Exception
     */
    protected function saveDefaultSettings(int $contextId, array $settings): void
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_contexts_settings');
        $existingSettings = $queryBuilder->select('*')
            ->from('tx_contexts_settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'context_uid',
                    $queryBuilder->createNamedParameter($contextId, PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'foreign_uid',
                    $queryBuilder->createNamedParameter(0, PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAllAssociative();

        foreach ($settings as $table => $fields) {
            $fieldSettings = [];
            foreach ($existingSettings as $setting) {
                if ($setting['foreign_table'] === $table) {
                    $fieldSettings[$setting['name']] = $setting['uid'];
                }
            }
            $connenction = $connectionPool->getConnectionForTable('tx_contexts_settings');
            foreach ($fields as $field => $enabled) {
                if (array_key_exists($field, $fieldSettings)) {
                    $connenction->update(
                        'tx_contexts_settings',
                        ['enabled' => (int)$enabled],
                        ['uid' => (int)$fieldSettings[$field]],
                        [PDO::PARAM_INT]
                    );
                } else {
                    $connenction->insert(
                        'tx_contexts_settings',
                        [
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => 0,
                            'enabled' => (int)$enabled
                        ],
                        [
                            PDO::PARAM_INT,
                            PDO::PARAM_STR,
                            PDO::PARAM_STR,
                            PDO::PARAM_INT,
                            PDO::PARAM_INT
                        ]
                    );
                }
            }
        }
    }
}
