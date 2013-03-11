<?php
class Tx_Contexts_Service_Tsfe
{
    /**
     * @param array $params
     * @param tslib_fe $ref
     */
    public function checkAlternativeIdMethodsPostProc($params, $ref)
    {
        $this->determineContexts();
    }
    
    public function determineContexts()
    {
        $currentContexts = Tx_Contexts_Api_Model::getCurrentContexts();
        $allContexts = Tx_Contexts_Api_Model::getContexts();
        foreach ($allContexts as $context) {
            /* @var $context Tx_Contexts_Context_Abstract */
            if ($context->match()) {
                $currentContexts->append($context);
            }
        }
    }
}