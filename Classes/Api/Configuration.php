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
 * General configuration API
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Tx_Contexts_Api_Configuration
{
    /**
     * The language file used for labels, added by contexts extension
     * @var string
     */
    const LANG_FILE = 'EXT:contexts/Resources/Private/Language/locallang_db.xml';

    /**
     * The name of the contexts settings column in TCA
     * (This is a USER field and will be handled by the TCEmain-
     * hooks in Tx_Contexts_Service_Tcemain)
     * @var string
     */
    const RECORD_SETTINGS_FIELD = 'tx_contexts_settings';

    /**
     * Array containing tables and extensions which added the
     * enableField to those tables (keys are tables, values
     * are arrays of extension keys)
     *
     * We need the pages table already in here because TCA has not
     * been loaded when the first page access check is being made.
     *
     * @var array
     */
    protected static $flatFields = array();

    protected static $extensionFlatFields = array();

    protected static $tcaCtrlEnablecolumns = array();

    /**
     * Add context settings to a specific table. $fields can be
     * any (boolean) settings, each with the name as key and array
     * containing the label:
     *
     * <code title="Adding the visibility (enableFields) setting only">
     * Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'some_table');
     * </code>
     *
     * <code title="Adding the visibility (enableFields) and another setting">
     * Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'some_table', array(
     *     'some_setting' => 'LLL:langfile:some_setting'
     * ));
     * </code>
     *
     * <code title="Adding another setting only">
     * Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'some_table', array(
     *     'some_setting' => 'LLL:langfile:some_setting'
     * ), false);
     * </code>
     *
     * <code title="Override the visibility (enableFields) setting label">
     * Tx_Contexts_Api_Configuration::addToTca($_EXTKEY, 'some_table', array(
     *     'tx_contexts_visibility' => 'LLL:langfile:my_label'
     * ));
     * </code>
     *
     * The extKey is necessary as it will be collected when you add the
     * enableField. A hook for the EM will then add the required
     * fields on $table on installation/update of your extension in EM.
     * (@see Tx_Contexts_Service_Install::appendTableDefinitions())
     *
     * @param string     $extKey      Extension key that ena the table
     * @param string     $table       Table to add settings to
     * @param array|null $fields      Array of fields to register.
     *                                Key is the field name, value its title
     * @param boolean    $addDefaults If an "enableField" is added that is
     *                                used to hide/show elements
     * @return void
     */
    public static function enableContextsForTable(
        $extKey, $table, $fields = null, $addDefaults = true
    ) {
        $defaultFields = array(
            'tx_contexts' => array(
                'label' => 'LLL:' . self::LANG_FILE . ':tx_contexts_visibility',
                'flatten' => true,
                'enableField' => true
            )
        );

        if (!is_array($fields)) {
            $fields = $addDefaults ? $defaultFields : array();
        } elseif ($addDefaults) {
            $fields = array_merge($defaultFields, $fields);
        }

        self::addToTcaCtrlEnablecolumns($table, $fields);
        self::addToFlatFields($table, $fields);

        if (TYPO3_MODE == 'BE') {
            self::addToTcaColumns($table, $fields);
            self::addToExtensionFlatFields($extKey, $table, $fields);
        }
    }

    /**
     * Determine if a field is a field to flatten
     * (enableFields currently require to be flattened)
     *
     * @param array $config
     * @return boolean
     */
    protected static function isFlatField($config) {
        return isset($config['flatten']) || isset($config['enableField']);
    }

    /**
     * Determine which of the fields should be flattened and add them to the
     * $table's $flatFields
     *
     * @param string $table
     * @param array $fields
     * @return void
     */
    protected static function addToFlatFields($table, $fields) {
        $flatFields = (array) self::$flatFields[$table];
        foreach ($fields as $field => $config) {
            if (self::isFlatField($config)) {
                $flatFields[$field] = array(
                    0 => $field . '_disable',
                    1 => $field . '_enable'
                );
            }
        }
        self::$flatFields[$table] = $flatFields;
    }

    /**
     * Same as self::addToFlatFields() but also tracks the $extKey (needed to
     * determine the database fields that need to be created on extension
     * installation/update)
     *
     * @param string $extKey
     * @param string $table
     * @param array $fields
     * @return void
     */
    protected static function addToExtensionFlatFields($extKey, $table, $fields)
    {
        $flatFields = array();
        foreach ($fields as $field => $config) {
            if (self::isFlatField($config)) {
                $flatFields[] = $field;
            }
        }
        if (!array_key_exists($extKey, self::$extensionFlatFields)) {
            self::$extensionFlatFields[$extKey] = array($table => $flatFields);
        } elseif (!array_key_exists($table, self::$extensionFlatFields[$extKey])) {
            self::$extensionFlatFields[$extKey][$table] = $flatFields;
        } else {
            self::$extensionFlatFields[$extKey][$table] = array_unique(array_merge(
                self::$extensionFlatFields[$extKey][$table],
                $fields
            ));
        }
    }

    /**
     * Add the settings with enableField set to the TCA enablecolumns - because
     * this can be overridden when the ext_tables.php are loaded later those
     * are also set in the internal self::$tcaCtrlEnablecolumns
     * Hence this should be used rather than the TCA by calling
     * Tx_Contexts_Api_Configuration::getTcaCtrlEnablecolumns()
     *
     * @param string $table
     * @param array $fields
     * @return void
     */
    protected static function addToTcaCtrlEnablecolumns($table, $fields) {
        global $TCA;
        $enableFields = (array) $TCA[$table]['ctrl']['enablecolumns']['tx_contexts'];
        foreach ($fields as $field => $config) {
            if (isset($config['enableField']) && !in_array($field, $enableFields)) {
                $enableFields = $field;
            }
        }
        $TCA[$table]['ctrl']['enablecolumns']['tx_contexts'] = $enableFields;
        self::$tcaCtrlEnablecolumns[$table] = $enableFields;
    }

    /**
     * Add field information to the TCA.
     *
     * @param string     $table          Table to add settings to
     * @param array|null $fields         Array of fields to register.
     *                                   Key is the field name, value its title
     * @return void
     */
    protected static function addToTcaColumns($table, array $fields) {
        global $TCA;
        t3lib_div::loadTCA($table);
        if (!isset($TCA[$table])) {
            return;
        }
        t3lib_div::loadTCA('tx_contexts_contexts');

        if (!array_key_exists(self::RECORD_SETTINGS_FIELD, $TCA[$table]['columns'])) {
            $recordSettingsConf = array(
                "exclude" => 1,
                "label" => '',
                "config" => array (
                    "type" => "user",
                    "size" => "30",
                    "userFunc" => 'Tx_Contexts_Service_Tca->renderRecordSettingsField',
                    'fields' => $fields
                )
            );
            t3lib_extMgm::addTCAcolumns($table, array(self::RECORD_SETTINGS_FIELD => $recordSettingsConf), 1);
            t3lib_extMgm::addToAllTCAtypes($table, '--div--;LLL:' . self::LANG_FILE . ':tabname,' . self::RECORD_SETTINGS_FIELD . ';;;;1-1-1');
        } else {
            $TCA[$table]['columns'][self::RECORD_SETTINGS_FIELD]['config']['fields'] = array_merge(
                $TCA[$table]['columns'][self::RECORD_SETTINGS_FIELD]['config']['fields'],
                $fields
            );
        }

        $defaultSettingsField = 'default_settings_' . $table;
        if (!array_key_exists($defaultSettingsField, $TCA['tx_contexts_contexts']['columns'])) {
            $defaultSettingsConf = array(
                "exclude" => 1,
                'label' => $TCA[$table]['ctrl']['title'],
                'config' => array(
                    'type' => 'user',
                    'size' => 30,
                    'userFunc' => 'Tx_Contexts_Service_Tca->renderDefaultSettingsField',
                    'table' => $table,
                    'fields' => $fields
                )
            );
            t3lib_extMgm::addTCAcolumns('tx_contexts_contexts', array($defaultSettingsField => $defaultSettingsConf), 1);
            t3lib_extMgm::addToAllTCAtypes('tx_contexts_contexts', $defaultSettingsField);
        } else {
            $TCA['tx_contexts_contexts']['columns'][$defaultSettingsField]['config']['fields'] = array_merge(
                $TCA['tx_contexts_contexts']['columns'][$defaultSettingsField]['config']['fields'],
                $fields
            );
        }
    }

    /**
     * Registers a context type with the context extension.
     * Makes it available in the context type dropdown
     * in the context record editor in the backend.
     *
     * @param string $key
     * @param string $title
     * @param string $class
     * @param string $flexFile
     * @return void
     */
    public static function registerContextType(
        $key, $title, $class, $flexFile
    ) {
        global $TCA;

        $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'][$key] = array(
            'title'    => $title,
            'class'    => $class,
            'flexFile' => $flexFile
        );

        t3lib_div::loadTCA('tx_contexts_contexts');

        if (isset($TCA['tx_contexts_contexts']['columns']['type'])) {
            $TCA['tx_contexts_contexts']['columns']['type']['config']
                ['items'][] = array($title, $key);
            $TCA['tx_contexts_contexts']['columns']['type_conf']['config']
                ['ds'][$key] = $flexFile;
        }
    }

    /**
     * Get the registered context types
     *
     * @return array
     */
    public static function getContextTypes() {
        return (array) $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'];
    }

    /**
     * Get all settings and theyr flat fields, or just of one table or just
     * of one table and field
     *
     * @param string|null $table
     * @param string|null $field
     * @return array $flatFields
     */
    public static function getFlatFields($table = null, $field = null) {
        if ($table) {
            if (isset(self::$flatFields[$table])) {
                if ($field) {
                    return self::$flatFields[$table][$field];
                } else {
                    return self::$flatFields[$table];
                }
            } else {
                return null;
            }
        }
        return self::$flatFields;
    }

    /**
     * Getter for $extensionFlatFields
     *
     * @return array $extensionFlatFields
     */
    public static function getExtensionFlatFields() {
        return self::$extensionFlatFields;
    }

    /**
     * Get the $TCA[$table][ctrl][enablecolumns][tx_contexts] without having to
     * rely on it
     *
     * @param string $table
     * @return string $tcaCtrlEnablecolumns
     */
    public static function getTcaCtrlEnablecolumns($table)
    {
        return (array) self::$tcaCtrlEnablecolumns[$table];
    }

    /**
     * Get the TYPO3_DB and it's type
     *
     * @return t3lib_db
     */
    public static function getDb()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
?>