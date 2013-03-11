<?php
class Tx_Contexts_Service_Page
{
    /**
     * Enter description here ...
     * @param array $params
     * @param t3lib_pageSelect $ref
     */
    public function enableFields($params, $ref)
    {
        $sql = '';
        $table = $params['table'];
        $enableFields = Tx_Contexts_Api_Configuration::getEnableFields();
        if (array_key_exists($table, $enableFields)) {
            $contextIds = array();
            foreach (Tx_Contexts_Api_Model::getCurrentContexts() as $context) {
            	/* @var $context Tx_Contexts_Context_Abstract */
                $contextIds[] = $context->getUid();
            }
            $settingsSelect =
            'SELECT COUNT(tx_contexts_settings.enabled) FROM tx_contexts_settings WHERE'.
            ' context_uid IN ('.implode(',', $contextIds).')'.
            " AND foreign_table = '".$table."'".
            " AND foreign_field IN ('".implode("','", $enableFields[$table])."')";
            $sql = ' AND ('.$settingsSelect.' AND enabled = 0 AND foreign_uid = '.$table.'.uid) = 0';
        }
        return $sql;
    }
}