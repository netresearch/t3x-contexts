<?php
class Tx_Contexts_Service_Tcemain
{
    protected $currentRules;

	/**
	 * Extract the context rules from the field array and set them in currentContextRules
	 * This function is called my TYPO each time a record is saved in the backend
	 *
	 * @param array $incomingFieldArray
	 * @param string $table
	 * @param string $id
	 * @param t3lib_TCEmain $reference
	 */
	function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, &$reference) {
		$data = $incomingFieldArray;

		if (!is_array($incomingFieldArray)) {
		    // some strange DB situation
			return;
		}

		if ($table == 'tx_contexts_contexts' &&
		    isset($incomingFieldArray['default_rules']) &&
		    is_array($incomingFieldArray['default_rules'])
		) {
		    $this->currentRules = $incomingFieldArray['default_rules'];
			unset($incomingFieldArray['default_rules']);
			return;
		}
		
		if (isset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_RULES_FIELD])) {
		    $this->currentRules = $incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_RULES_FIELD];
		    unset($incomingFieldArray[Tx_Contexts_Api_Configuration::RECORD_RULES_FIELD]);
		}
	}

	/**
	 * Finally save the rules
	 *
	 * @param string $status
	 * @param string $table
	 * @param string $id
	 * @param array $fieldArray
	 * @param t3lib_TCEmain $reference
	 */
	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $reference)
	{
	    if (is_array($this->currentRules)) {
	        if (!t3lib_div::testInt($id)) {
	            $id = $reference->substNEWwithIDs[$id];
	        }
	        if ($table == 'tx_contexts_contexts') {
    	        $this->saveDefaultRules($id, $this->currentRules);
	        } else {
    	        $this->saveRecordRules($table, $id, $this->currentRules);
	        }
	    
		    unset($this->currentRules);
	    }
	}
	
	protected function saveRecordRules($table, $uid, $rulesAndFields)
	{
	    $db = Tx_Contexts_Api_Model::getDb();
	    foreach ($rulesAndFields as $contextId => $rules) {
	        foreach ($rules as $field => $rule) {
                $row = $db->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_contexts_rules',
                    "context_uid = $contextId AND ".
                    "foreign_table = '$table' AND ".
                    "foreign_field = '$field' AND ".
                    "foreign_uid = $uid"
                );
	            if ($rule === '0' || $rule === '1') {
	                if ($row) {
	                    $db->exec_UPDATEquery('tx_contexts_rules', 'uid='.$row['uid'], array('enabled' => $rule));
	                } else {
	                    $db->exec_INSERTquery('tx_contexts_rules', array(
	                        'context_uid' => $contextId,
	                        'foreign_table' => $table,
	                        'foreign_field' => $field,
	                        'foreign_uid' => $uid,
	                    	'enabled' => $rule
	                    ));
	                }
	            } elseif ($row) {
	                $db->exec_DELETEquery('tx_contexts_rules', 'uid='.$row['uid']);
	            }
	        }
	    }
	}
	
	protected function saveDefaultRules($contextId, $rules)
	{
	    $rules = Tx_Contexts_Api_Model::getDb()->exec_SELECTgetRows(
	    	'*',
	    	'tx_contexts_rules',
	    	"context_uid = '$contextId' AND foreign_uid = 0"
	    );

		foreach ($rules as $table => $fields) {
		    $fieldRules = array();
		    foreach ($rules as $rule) {
		        if ($rule['foreign_table'] == $table) {
		            $fieldRules[$rule['foreign_field']] = $rule['uid'];
		        }
		    }
		    foreach ($fields as $field => $enabled) {
		        if (array_key_exists($field, $fieldRules)) {
		            Tx_Contexts_Api_Model::getDb()->exec_UPDATEquery(
		            	'tx_contexts_rules',
		            	'uid='.$fieldRules[$field],
		                array('enabled' => (int) $enabled)
		            );
		        } else {
		            Tx_Contexts_Api_Model::getDb()->exec_INSERTquery(
		            	'tx_contexts_rules',
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