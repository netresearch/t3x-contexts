<?php
class Tx_Contexts_Context_Type_Default extends Tx_Contexts_Context_Abstract
{
    public function __construct($arRow = array())
    {
        $this->uid = 0;
        $this->type = $this->title = __CLASS__;
        $this->noIsVoidable = true;
    }


    public function match(array $arDependencies = array())
    {
        return true;
    }
}
