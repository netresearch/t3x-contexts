<?php
class Tx_Contexts_Service_Tca
{
    /**
     * Render the context visibility field for a certain table
     *
     * @param array $PA
     * @param t3lib_TCEforms $fobj
     * @return string
     */
    public function renderRecordSettingsField($PA, $fobj)
    {
        global $TCA;
        $table = $PA['table'];

        $fobj->addStyleSheet(
        	'tx_contexts_bestyles',
            t3lib_extMgm::extRelPath('contexts').'Resources/Public/StyleSheet/be.css'
        );

        $contexts = Tx_Contexts_Api_Model::getContexts();
        
	    $namePre = str_replace('['.$PA['field'].'_', '['.$PA['field'].'][', $PA['itemFormElName']);

	    $fields = $PA['fieldConf']['config']['fields'];

	    $content =
	    '<table class="tx_contexts_table_settings">'.
	    '<tr><th class="tx_contexts_context">'.
	    $fobj->sL('LLL:'.Tx_Contexts_Api_Configuration::LANG_FILE.':tx_contexts_context').
	    '</th>';
	    foreach ($fields as $field => $config) {
	        $content .= '<th class="tx_contexts_setting">'.$fobj->sL($config['label']).'</th>';
	    }
	    $content .= '</tr>';
	    
	    $uid = (int) $PA['row']['uid'];
	    
	    foreach ($contexts as $context) {
	        /* @var $context Tx_Contexts_Context_Abstract */
	        $content .= '<tr><td class="tx_contexts_context">'.$context->getTitle().'</td>';
	        
	        foreach ($fields as $field => $config) {
	            $setting = $uid ? $context->getSetting($table, $uid, $field) : null;
	            $content .=
	            '<td class="tx_contexts_setting">'.
	            '<select name="'.$namePre.'['.$context->getUid().']['.$field.']">'.
	            '<option value="">n/a</option>'.
	            '<option value="1"'.($setting && $setting->getEnabled() ? ' selected="selected"' : '').'>Yes</option>'.
	            '<option value="0"'.($setting && !$setting->getEnabled() ? ' selected="selected"' : '').'>No</option>'.
	            '</select></td>';
	        }
	        
	        $content .= '</tr>';
	    }
	    $content .= '</table>';

        return $content;
    }

    /**
     * Render a checkbox for the default visibility of records in
     * this table
     *
     * @param array $PA
     * @param t3lib_TCEforms $fobj
     * @return string
     */
    public function renderDefaultSettingsField($PA, $fobj)
    {
        global $TCA;
        $table = $PA['fieldConf']['config']['table'];
        t3lib_div::loadTCA($table);

        $content = '';

        $namePre = str_replace('[default_settings_', '[default_settings][', $PA['itemFormElName']);

        /* @var $context Tx_Contexts_Context_Abstract */
        $uid = (int) $PA['row']['uid'];
        $context = $uid ? Tx_Contexts_Api_Model::getContext($uid) : null;

        foreach ($PA['fieldConf']['config']['fields'] as $field => $config) {
            $id = $PA['itemFormElID'].'-'.$field;
            $name = $namePre.'['.$field.']';
            $content .= '<input type="hidden" name="'.$name.'" value="0"/>';
            $content .= '<input class="checkbox" type="checkbox" name="'.$name.'" ';
            if (
                !$context ||
                !$context->hasSetting($table, 0, $field) ||
                $context->getSetting($table, 0, $field)->getEnabled()
            ) {
                $content .= 'checked="checked" ';
            }
            $content .= 'value="1" id="'.$id.'" /> ';
            $content .= '<label for="'.$id.'">';
            $content .= $fobj->sL($TCA[$table]['columns'][$field]['label']);
            $content .= '</label><br/>';
        }

        return $content;
    }

}