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
     * The name of the enableField setting - used as identifier
     * in $TCA[$table]['ctrl']['enablecolumns'] and as setting
     * field name
     * @var string
     */
    const ENABLE_FIELD = 'tx_contexts_visibility';

    /**
     * The name of the enableField column with a comma separated
     * list of contexts for which a record is ENABLED
     * (to enable faster queries with enableFields)
     * @see Tx_Contexts_Service_Page::enableFields()
     * @see Tx_Contexts_Service_Tcemain::saveRecordSettings()
     * @var string
     */
    const ENABLE_COLUMN_ENABLE = 'tx_contexts_enable';

    /**
     * The name of the enableField column with a comma separated
     * list of contexts for which a record is DISABLED
     * (to enable faster queries with enableFields)
     * @see Tx_Contexts_Service_Page::enableFields()
     * @see Tx_Contexts_Service_Tcemain::saveRecordSettings()
     * @var string
     */
    const ENABLE_COLUMN_DISABLE = 'tx_contexts_disable';

    /**
     * Array containing tables and extensions which added the
     * enableField to those tables (keys are tables, values
     * are arrays of extension keys)
     * @var array
     */
    protected static $enableFieldsExtensions = array();

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
     *     Tx_Contexts_Api_Configuration::ENABLE_FIELD => 'LLL:langfile:my_label'
     * ));
     * </code>
     *
     * The extKey is necessary as it will be collected when you add the
     * enableField. A hook for the EM will then add the required
     * fields on $table on installation/update of your extension in EM.
     * (@see Tx_Contexts_Service_Install::appendTableDefinitions())
     *
     * @param string $extKey
     * @param string $table
     * @param array|null $fields
     * @param boolean $addEnableField
     * @return void
     */
    public static function addToTca($extKey, $table, $fields = null, $addEnableField = true)
    {
        global $TCA;
        t3lib_div::loadTCA($table);
        t3lib_div::loadTCA('tx_contexts_contexts');

        if ($addEnableField) {
            $TCA[$table]['ctrl']['enablecolumns'][self::ENABLE_FIELD] = self::ENABLE_FIELD;
            if (!array_key_exists($table, self::$enableFieldsExtensions)) {
                self::$enableFieldsExtensions[$table] = array();
            }
            self::$enableFieldsExtensions[$table][] = $extKey;
        }

        $defaultEnableField = array(
            self::ENABLE_FIELD => 'LLL:' . self::LANG_FILE . ':' . self::ENABLE_FIELD,
        );

        if (!is_array($fields)) {
            $fields = $addEnableField ? $defaultEnableField : array();
        } elseif ($addEnableField) {
            $fields = array_merge($defaultEnableField, $fields);
        }

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

        if ($addEnableField && !array_key_exists(self::ENABLE_COLUMN_ENABLE, $TCA[$table]['columns'])) {
            $enableColumns = array(
                self::ENABLE_COLUMN_ENABLE => $conf = array(
                    'config' => array(
                        'type' => 'passthrough'
                    )
                ),
                self::ENABLE_COLUMN_DISABLE => $conf
            );
            t3lib_extMgm::addTCAcolumns($table, $enableColumns, true);
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
    public static function getContextTypes()
    {
        return (array) $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'];
    }

    /**
     * Getter for $enableFieldsExtensions
     *
     * @return array $enableFieldsExtensions
     */
    public static function getEnableFieldsExtensions()
    {
        return self::$enableFieldsExtensions;
    }

    /**
     * @return t3lib_db
     */
    public static function getDb()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
?>