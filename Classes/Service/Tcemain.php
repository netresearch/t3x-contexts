<?php
class Tx_Contexts_Service_Tcemain
{
    protected $currentSettings;

	/**
	 * Extract the context settings from the field array and set them in currentContextSettings
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
	 */
	function processDatamap_afterDatabaseOperations($status, $table, $id, $fieldArray, $reference)
	{
	    if (is_array($this->currentSettings)) {
	        if (!t3lib_div::testInt($id)) {
	            $id = $reference->substNEWwithIDs[$id];
	        }
	        if ($table == 'tx_contexts_contexts') {
    	        $this->saveDefaultSettings($id, $this->currentSettings);
	        } else {
    	        $this->saveRecordSettings($table, $id, $this->currentSettings);
	        }
	    
		    unset($this->currentSettings);
	    }
	}
	
	protected function saveRecordSettings($table, $uid, $settingsAndFields)
	{
	    $db = Tx_Contexts_Api_Model::getDb();
	    foreach ($settingsAndFields as $contextId => $settings) {
	        foreach ($settings as $field => $setting) {
                $row = $db->exec_SELECTgetSingleRow(
                    'uid',
                    'tx_contexts_settings',
                    "context_uid = $contextId AND ".
                    "foreign_table = '$table' AND ".
                    "foreign_field = '$field' AND ".
                    "foreign_uid = $uid"
                );
	            if ($setting === '0' || $setting === '1') {
	                if ($row) {
	                    $db->exec_UPDATEquery('tx_contexts_settings', 'uid='.$row['uid'], array('enabled' => $setting));
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
	                $db->exec_DELETEquery('tx_contexts_settings', 'uid='.$row['uid']);
	            }
	        }
	    }
	}
	
	protected function saveDefaultSettings($contextId, $settings)
	{
	    $existingSettings = Tx_Contexts_Api_Model::getDb()->exec_SELECTgetRows(
	    	'*',
	    	'tx_contexts_settings',
	    	"context_uid = '$contextId' AND foreign_uid = 0"
	    );
        if ($existingSettings === null) {
            $existingSettings = array();
        }

		foreach ($settings as $table => $fields) {
		    $fieldSettings = array();
		    foreach ($existingSettings as $setting) {
		        if ($setting['foreign_table'] == $table) {
		            $fieldSettings[$setting['foreign_field']] = $setting['uid'];
		        }
		    }
		    foreach ($fields as $field => $enabled) {
		        if (array_key_exists($field, $fieldSettings)) {
		            Tx_Contexts_Api_Model::getDb()->exec_UPDATEquery(
		            	'tx_contexts_settings',
		            	'uid='.$fieldSettings[$field],
		                array('enabled' => (int) $enabled)
		            );
		        } else {
		            Tx_Contexts_Api_Model::getDb()->exec_INSERTquery(
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