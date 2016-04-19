<?php
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

/**
 * Class for TCEmain-hooks: Capture incoming default and record settings
 * and save them to the settings table and the enabled fields
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Tx_Contexts_Service_Tcemain
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
     * @param t3lib_TCEmain &$reference
     * @return void
     */
    public function processDatamap_preProcessFieldArray(
        &$incomingFieldArray, $table, $id, &$reference
    ) {
        $data = $incomingFieldArray;

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

        if (isset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_COLUMN])) {
            $this->currentSettings = $incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_COLUMN];
            unset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_COLUMN]);
        }
    }

    /**
     * Finally save the settings
     *
     * @param string        $status
     * @param string        $table
     * @param string        $id
     * @param array         $fieldArray
     * @param t3lib_TCEmain $reference
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
        $db = Tx_Contexts_Api_Configuration::getDb();
        $flatSettingColumns = Tx_Contexts_Api_Configuration::getFlatColumns(
            $table
        );

        foreach ($contextsAndSettings as $contextId => $settings) {
            foreach ($settings as $field => $setting) {
                if (isset($flatSettingColumns[$field])) {
                    continue;
                }
                $row = $db->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_contexts_settings',
                    "context_uid = $contextId AND " .
                    "foreign_table = '$table' AND " .
                    "name = '$field' AND " .
                    "foreign_uid = $uid"
                );
                if ($setting === '0' || $setting === '1') {
                    if ($row) {
                        $db->exec_UPDATEquery('tx_contexts_settings', 'uid=' . $row['uid'], array('enabled' => $setting));
                    } else {
                        $db->exec_INSERTquery('tx_contexts_settings', array(
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => $uid,
                            'enabled' => $setting
                        ));
                    }
                } elseif ($row) {
                    $db->exec_DELETEquery('tx_contexts_settings', 'uid=' . $row['uid']);
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
     * @see Tx_Contexts_Service_Tsfe::enableFields()
     */
    protected function saveFlatSettings($table, $uid, $contextsAndSettings)
    {
        $values = array();

        $flatSettingColumns = Tx_Contexts_Api_Configuration::getFlatColumns($table);
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
            Tx_Contexts_Api_Configuration::getDb()->exec_UPDATEquery(
                $table, 'uid=' . $uid, $values
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
        $existingSettings = (array) Tx_Contexts_Api_Configuration::getDb()->exec_SELECTgetRows(
            '*',
            'tx_contexts_settings',
            "context_uid = '$contextId' AND foreign_uid = 0"
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
                    Tx_Contexts_Api_Configuration::getDb()->exec_UPDATEquery(
                        'tx_contexts_settings',
                        'uid=' . $fieldSettings[$field],
                        array('enabled' => (int) $enabled)
                    );
                } else {
                    Tx_Contexts_Api_Configuration::getDb()->exec_INSERTquery(
                        'tx_contexts_settings',
                        array(
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'name' => $field,
                            'foreign_uid' => 0,
                            'enabled' => (int) $enabled
                        )
                    );
                }
            };
        }
    }
}
?>