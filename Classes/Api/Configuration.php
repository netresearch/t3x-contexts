<?php
class Tx_Contexts_Api_Configuration
{
    const LANG_FILE = 'EXT:contexts/Resources/Private/Language/locallang_db.xml';

    const RECORD_RULES_FIELD = 'tx_contexts_rules';

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

        if (!array_key_exists(self::RECORD_RULES_FIELD, $TCA[$table]['columns'])) {
            $recordRulesConf = array(
        		"exclude" => 1,
        		"label" => 'Huhu',
        		"config" => array (
        			"type" => "user",
        			"size" => "30",
        			"userFunc" => 'Tx_Contexts_Service_Tca->renderRecordRulesField',
                    'fields' => $fields
        		)
            );
            t3lib_extMgm::addTCAcolumns($table, array(self::RECORD_RULES_FIELD => $recordRulesConf), 1);
            t3lib_extMgm::addToAllTCAtypes($table, '--div--;LLL:'.self::LANG_FILE.':tabname,'.self::RECORD_RULES_FIELD.';;;;1-1-1');
        } else {
            $TCA[$table]['columns'][self::RECORD_RULES_FIELD]['config']['fields'] = array_merge(
                $TCA[$table]['columns'][self::RECORD_RULES_FIELD]['config']['fields'],
                $fields
            );
        }

        $defaultRulesField = 'default_rules_'.$table;
        if (!array_key_exists($defaultRulesField, $TCA['tx_contexts_contexts']['columns'])) {
            $defaultRulesConf = array(
        		"exclude" => 1,
                'label' => $TCA[$table]['ctrl']['title'],
                'config' => array(
                    'type' => 'user',
                    'size' => 30,
                    'userFunc' => 'Tx_Contexts_Service_Tca->renderDefaultRulesField',
                    'table' => $table,
                    'fields' => $fields
                )
            );
            t3lib_extMgm::addTCAcolumns('tx_contexts_contexts', array($defaultRulesField => $defaultRulesConf), 1);
            t3lib_extMgm::addToAllTCAtypes('tx_contexts_contexts', $defaultRulesField);
        } else {
            $TCA['tx_contexts_contexts']['columns'][$defaultRulesField]['config']['fields'] = array_merge(
                $TCA['tx_contexts_contexts']['columns'][$defaultRulesField]['config']['fields'],
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

    public static function registerContextType($type, $class)
    {
        $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'][$type] = $class;
    }

    public static function getContextTypes()
    {
        return (array) $GLOBALS['EXTCONF']['tx_contexts']['contextTypes'];
    }
}