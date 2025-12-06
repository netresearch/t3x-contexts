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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Class for TCEmain-hooks: Capture incoming default and record settings
 * and save them to the settings table and the enabled fields
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class DataHandlerService
{
    /**
     * @var array<int|string, array<string, string>>
     */
    protected array $currentSettings = [];

    public function __construct(private readonly ConnectionPool $connectionPool)
    {
    }

    /**
     * Extract the context settings from the field array and set them in
     * currentSettings. This function is called by TYPO each time a record
     * is saved in the backend.
     *
     *
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function processDatamap_preProcessFieldArray(
        array &$incomingFieldArray,
        string $table,
        string $id,
        DataHandler $reference,
    ): void {
        if (
            $table === 'tx_contexts_contexts'
            && isset($incomingFieldArray['default_settings'])
            && \is_array($incomingFieldArray['default_settings'])
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
     *
     *
     * @throws DBALException
     * @throws Exception
     */
    // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string $id,
        array $fieldArray,
        DataHandler $reference,
    ): void {
        if (
            isset($this->currentSettings)
            && (\count($this->currentSettings) > 0)
        ) {
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
     *
     *
     * @throws DBALException
     * @throws Exception
     */
    protected function saveRecordSettings(string $table, int $uid, array $contextsAndSettings): void
    {
        $flatSettingColumns = Configuration::getFlatColumns($table);
        $connectionPool = $this->connectionPool;

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
                            $queryBuilder->createNamedParameter((int) $contextId, Connection::PARAM_INT),
                        ),
                        $queryBuilder->expr()->eq(
                            'foreign_table',
                            $queryBuilder->createNamedParameter($table),
                        ),
                        $queryBuilder->expr()->eq(
                            'name',
                            $queryBuilder->createNamedParameter('tx_contexts_settings'),
                        ),
                        $queryBuilder->expr()->in(
                            'foreign_uid',
                            $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT),
                        ),
                    )
                    ->executeQuery()
                    ->fetchAssociative();
                $connection = $connectionPool->getConnectionForTable('tx_contexts_settings');
                if ((int) $setting === 0 || (int) $setting === 1) {
                    if ($row !== false) {
                        $connection->update(
                            'tx_contexts_settings',
                            ['enabled' => (int) $setting],
                            ['uid' => (int) $row['uid']],
                            [Connection::PARAM_INT],
                        );
                    } else {
                        $connection->insert(
                            'tx_contexts_settings',
                            [
                                'context_uid' => (int) $contextId,
                                'foreign_table' => $table,
                                'name' => $field,
                                'foreign_uid' => $uid,
                                'enabled' => (int) $setting,
                            ],
                            [
                                Connection::PARAM_INT,
                                Connection::PARAM_STR,
                                Connection::PARAM_STR,
                                Connection::PARAM_INT,
                                Connection::PARAM_INT,
                            ],
                        );
                    }
                } elseif ($row !== false) {
                    $connection->delete(
                        'tx_contexts_settings',
                        ['uid' => (int) $row['uid']],
                        [Connection::PARAM_INT],
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
     * @param array  $contextsAndSettings Array of settings.
     *                                    Key is the context UID.
     *                                    Value is an array of setting names
     *                                    and their value, e.g.
     *                                    tx_contexts_visibility => '',
     *                                    menu_visibility => '0'
     *                                    '' = undecided, 1 - on, 0 - off
     *
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

        if (\count($values) > 0) {
            foreach ($values as $colname => &$val) {
                $val = implode(',', $val);
            }

            $connection = $this->connectionPool
                ->getConnectionForTable($table);

            $connection->update(
                $table,
                $values,
                ['uid' => $uid],
            );
        }
    }

    /**
     * Save the default settings to the settings table - default
     * settings will have a foreign_uid of 0
     *
     *
     *
     * @throws DBALException
     * @throws Exception
     */
    protected function saveDefaultSettings(int $contextId, array $settings): void
    {
        $connectionPool = $this->connectionPool;

        $queryBuilder = $connectionPool->getQueryBuilderForTable('tx_contexts_settings');
        $existingSettings = $queryBuilder->select('*')
            ->from('tx_contexts_settings')
            ->where(
                $queryBuilder->expr()->eq(
                    'context_uid',
                    $queryBuilder->createNamedParameter($contextId, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'foreign_uid',
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
                ),
            )
            ->executeQuery()
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
                if (\array_key_exists($field, $fieldSettings)) {
                    $connenction->update(
                        'tx_contexts_settings',
                        ['enabled' => (int) $enabled],
                        ['uid' => (int) $fieldSettings[$field]],
                        [Connection::PARAM_INT],
                    );
                } else {
                    $connenction->insert(
                        'tx_contexts_settings',
                        [
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => 0,
                            'enabled' => (int) $enabled,
                        ],
                        [
                            Connection::PARAM_INT,
                            Connection::PARAM_STR,
                            Connection::PARAM_STR,
                            Connection::PARAM_INT,
                            Connection::PARAM_INT,
                        ],
                    );
                }
            }
        }
    }
}
