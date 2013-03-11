<?php
class Tx_Contexts_Context_Factory
{
    /**
     * @param array $row
     * @return Tx_Contexts_Context_Abstract
     */
    public static function makeContextInstance($row)
    {
        $classMap = Tx_Contexts_Api_Configuration::getContextTypes();
        $type = $row['type'];
        if (!$type || !array_key_exists($type, $classMap)) {
            t3lib_div::devLog('No class found for context type "'.$type.'"', 'tx_contexts', 2);
            $type = 'default';
        }
        $class = $classMap[$type];
        $instance = t3lib_div::makeInstance($class, $row);
        if ($instance instanceof t3lib_Singleton) {
            throw new Tx_Contexts_Exception($class.' may not be singleton');
        }
        if (!$instance instanceof Tx_Contexts_Context_Abstract) {
            throw new Tx_Contexts_Exception($class.' must extend Tx_Contexts_Context_Abstract');
        }
        return $instance;
    }
}