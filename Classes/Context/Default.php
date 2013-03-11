<?php
class Tx_Contexts_Context_Default extends Tx_Contexts_Context_Abstract
{
    public function match()
    {
        if (!array_key_exists('context', $_GET)) {
            return false;
        }
        return $_GET['context'] === $this->getAlias() || $_GET['context'] == $this->getUid();
    }
}