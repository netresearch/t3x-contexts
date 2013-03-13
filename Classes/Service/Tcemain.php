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
     * @param array $incomingFieldArray
     * @param string $table
     * @param string $id
     * @param t3lib_TCEmain $reference
     * @return void
     */
    function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$reference) {
        $data = $incomingFieldArray;

        if (!is_array($incomingFieldArray)) {
                // some strange DB situation
            return;
        }

        if ($table == 'tx_contexts_contexts' &&
            isset($incomingFieldArray['default_settings']) &&
            is_array($incomingFieldArray['default_settings'])
        ) {
            $this->currentSettings = $incomingFieldArray['default_settings'];
            unset($incomingFieldArray['default_settings']);
            return;
        }

        if (isset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_FIELD])) {
            $this->currentSettings = $incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_FIELD];
            unset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_SETTINGS_FIELD]);
        }
    }

    /**
     * Finally save the settings
     *
     * @param string $status
     * @param string $table
     * @param string $id
     * @param array $fieldArray
     * @param t3lib_TCEmain $reference
     * @return void
     */
    function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $reference)
    {
        if (is_array($this->currentSettings)) {
            if (!is_numeric($id)) {
                $id = $reference->substNEWwithIDs[$id];
            }
            if ($table == 'tx_contexts_contexts') {
                $this->saveDefaultSettings($id, $this->currentSettings);
            } else {
                $this->saveRecordSettings($table, $id, $this->currentSettings);
                $this->saveEnableField($table, $id, $this->currentSettings);
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
     * @param int $uid
     * @param array $settingsAndFields
     * @return void
     */
    protected function saveRecordSettings($table, $uid, $settingsAndFields)
    {
        $db = Tx_Contexts_Api_Configuration::getDb();
        foreach ($settingsAndFields as $contextId => $settings) {
            foreach ($settings as $field => $setting) {
                $row = $db->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_contexts_settings',
                    "context_uid = $contextId AND " .
                    "foreign_table = '$table' AND " .
                    "foreign_field = '$field' AND " .
                    "foreign_uid = $uid"
                );
                if ($setting === '0' || $setting === '1') {
                    if ($row) {
                        $db->exec_UPDATEquery('tx_contexts_settings', 'uid=' . $row['uid'], array('enabled' => $setting));
                    } else {
                        $db->exec_INSERTquery('tx_contexts_settings', array(
                            'context_uid' => $contextId,
                            'foreign_table' => $table,
                            'foreign_field' => $field,
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
     * Saves the visibility settings (enableFields) to the enableFields
     * columns on the table to allow quicker queries in enableField
     * hook.
     *
     * @param string $table
     * @param int $uid
     * @param array $settingsAndFields
     * @return void
     * @see Tx_Contexts_Service_Tsfe::enableFields()
     */
    protected function saveEnableField($table, $uid, $settingsAndFields)
    {
        $values = array();
        $enableField = Tx_Contexts_Api_Configuration::ENABLE_FIELD;
        foreach ($settingsAndFields as $contextId => $settings) {
            if (array_key_exists($enableField, $settings)) {
                if ($settings[$enableField] === '0') {
                    $column = Tx_Contexts_Api_Configuration::ENABLE_COLUMN_DISABLE;
                } elseif ($settings[$enableField] === '1') {
                    $column = Tx_Contexts_Api_Configuration::ENABLE_COLUMN_ENABLE;
                } else {
                    continue;
                }
                if (!array_key_exists($column, $values)) {
                    $values[$column] = $contextId;
                } else {
                    $values[$column] .= ',' . $contextId;
                }
            }
        }
        if (count($values)) {
            Tx_Contexts_Api_Configuration::getDb()->exec_UPDATEquery($table, 'uid=' . $uid, $values);
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
                    $fieldSettings[$setting['foreign_field']] = $setting['uid'];
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
                            'foreign_field' => $field,
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