<?php
class Tx_Contexts_Api_Configuration
{
    const LANG_FILE = 'EXT:contexts/Resources/Private/Language/locallang_db.xml';

    const RECORD_SETTINGS_FIELD = 'tx_contexts_settings';

    const DEFAULT_FIELD = 'tx_contexts_visibility';
    
    protected static $enableFields = array();

    public static function addToTca($table, $fields = null, $addDefaultField = true)
    {
        global $TCA;
        t3lib_div::loadTCA($table);
        t3lib_div::loadTCA('tx_contexts_contexts');

        $defaultField = array(
            self::DEFAULT_FIELD => array(
            	'label' => 'LLL:'.self::LANG_FILE.':'.self::DEFAULT_FIELD,
            	'enableField' => true
            )
        );
        
        if (!is_array($fields)) {
            $fields = $defaultField;
        } elseif ($addDefaultField && !array_key_exists(self::DEFAULT_FIELD, $fields)) {
            $fields = array_merge($defaultField, $fields);
        }
        
        foreach ($fields as $field => $config) {
            if (array_key_exists('enableField', $config) && $config['enableField']) {
                if (!array_key_exists($table, self::$enableFields)) {
                    self::$enableFields[$table] = array();
                }
                self::$enableFields[$table][$field] = $field;
            }
        }

        if (!array_key_exists(self::RECORD_SETTINGS_FIELD, $TCA[$table]['columns'])) {
            $recordSettingsConf = array(
        		"exclude" => 1,
        		"label" => 'Huhu',
        		"config" => array (
        			"type" => "user",
        			"size" => "30",
        			"userFunc" => 'Tx_Contexts_Service_Tca->renderRecordSettingsField',
                    'fields' => $fields
        		)
            );
            t3lib_extMgm::addTCAcolumns($table, array(self::RECORD_SETTINGS_FIELD => $recordSettingsConf), 1);
            t3lib_extMgm::addToAllTCAtypes($table, '--div--;LLL:'.self::LANG_FILE.':tabname,'.self::RECORD_SETTINGS_FIELD.';;;;1-1-1');
        } else {
            $TCA[$table]['columns'][self::RECORD_SETTINGS_FIELD]['config']['fields'] = array_merge(
                $TCA[$table]['columns'][self::RECORD_SETTINGS_FIELD]['config']['fields'],
                $fields
            );
        }

        $defaultSettingsField = 'default_settings_'.$table;
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
     * @return array
     */
    public static function getEnableFields()
    {
        return self::$enableFields;
    }

    /**
     * Registers a context type with the context extension.
     * Makes it available in the context type dropdown
     * in the context record editor in the backend.
     *
     * @return void
     */
    public static function registerContextType(
        $key, $title, $class, $flexFile
    ) {
        global $TCA;

        $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'][$key] = array(
            'title'     => $title,
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

    public static function getContextTypes()
    {
        return (array) $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'];
    }
}