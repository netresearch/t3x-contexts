<?php
namespace Bmack\Contexts\Service;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use Bmack\Contexts\Api\Configuration;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\DataHandling\DataHandler;

/**
 * Class for TCEmain-hooks: Capture incoming default and record settings
 * and save them to the settings table and the enabled fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class DataHandlerService
{
    protected $currentSettings;

    /**
     * Extract the context settings from the field array and set them in
     * currentSettings. This function is called by TYPO each time a record
     * is saved in the backend.
     *
     * @param array         &$incomingFieldArray
     * @param string        $table
     * @param string        $id
     * @param DataHandler   &$reference
     * @return void
     */
    public function processDatamap_preProcessFieldArray(
        &$incomingFieldArray, $table, $id, &$reference
    ) {
        if (!is_array($incomingFieldArray)) {
            // some strange DB situation
            return;
        }

        if ($table == 'tx_contexts_contexts'
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
     * @param string        $status
     * @param string        $table
     * @param string        $id
     * @param array         $fieldArray
     * @param DataHandler   $reference
     * @return void
     */
    public function processDatamap_afterDatabaseOperations(
        $status, $table, $id, $fieldArray, $reference
    ) {
        if (is_array($this->currentSettings)) {
            if (!is_numeric($id)) {
                $id = $reference->substNEWwithIDs[$id];
            }
            if ($table == 'tx_contexts_contexts') {
                $this->saveDefaultSettings($id, $this->currentSettings);
            } else {
                $this->saveRecordSettings($table, $id, $this->currentSettings);
                $this->saveFlatSettings($table, $id, $this->currentSettings);
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
     * @return void
     */
    protected function saveRecordSettings($table, $uid, $contextsAndSettings)
    {
        $flatSettingColumns = Configuration::getFlatColumns(
            $table
        );
        /** @var DatabaseConnection $databaseConnection */
        $databaseConnection = $GLOBALS['TYPO3_DB'];

        foreach ($contextsAndSettings as $contextId => $settings) {
            foreach ($settings as $field => $setting) {
                if (isset($flatSettingColumns[$field])) {
                    continue;
                }
                $row = $databaseConnection->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_contexts_settings',
                    'context_uid = ' . (int)$contextId .  ' AND ' .
                    'foreign_table = '. $databaseConnection->fullQuoteStr($table, 'tx_contexts_settings') . ' AND ' .
                    'name = ' . $databaseConnection->fullQuoteStr($field, 'tx_contexts_settings') . ' AND ' .
                    'foreign_uid = ' . (int)$uid
                );
                if ($setting === '0' || $setting === '1') {
                    if ($row) {
                        $databaseConnection->exec_UPDATEquery('tx_contexts_settings', 'uid=' . (int)$row['uid'], array('enabled' => $setting));
                    } else {
                        $databaseConnection->exec_INSERTquery('tx_contexts_settings', array(
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => $uid,
                            'enabled' => $setting
                        ));
                    }
                } elseif ($row) {
                    $databaseConnection->exec_DELETEquery('tx_contexts_settings', 'uid=' . (int)$row['uid']);
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
     * @return void
     * @see FrontendControllerService::enableFields()
     */
    protected function saveFlatSettings($table, $uid, $contextsAndSettings)
    {
        $values = array();

        $flatSettingColumns = Configuration::getFlatColumns($table);
        foreach ($flatSettingColumns as $setting => $flatColumns) {
            $values[$flatColumns[0]] = array();
            $values[$flatColumns[1]] = array();
            foreach ($contextsAndSettings as $contextId => $settings) {
                if ($settings[$setting] === '0' || $settings[$setting] === '1') {
                    $values[$flatColumns[$settings[$setting]]][] = $contextId;
                }
            }
        }

        if (count($values)) {
            foreach ($values as $colname => &$val) {
                $val = implode(',', $val);
            }
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                $table, 'uid=' . (int)$uid, $values
            );
        }
    }

    /**
     * Save the default settings to the settings table - default
     * settings will have a foreign_uid of 0
     *
     * @param int $contextId
     * @param array $settings
     * @return void
     */
    protected function saveDefaultSettings($contextId, $settings)
    {
        $existingSettings = (array)$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            '*',
            'tx_contexts_settings',
            'context_uid = ' . (int)$contextId . ' AND foreign_uid = 0'
        );

        foreach ($settings as $table => $fields) {
            $fieldSettings = array();
            foreach ($existingSettings as $setting) {
                if ($setting['foreign_table'] == $table) {
                    $fieldSettings[$setting['name']] = $setting['uid'];
                }
            }
            foreach ($fields as $field => $enabled) {
                if (array_key_exists($field, $fieldSettings)) {
                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        'tx_contexts_settings',
                        'uid=' . (int)$fieldSettings[$field],
                        array('enabled' => (int)$enabled)
                    );
                } else {
                    $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                        'tx_contexts_settings',
                        array(
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => 0,
                            'enabled' => (int)$enabled
                        )
                    );
                }
            }
        }
    }
}