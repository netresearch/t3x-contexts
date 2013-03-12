<?php
class Tx_Contexts_Api_Model
{
    /**
     * @var array
     */
    protected static $loadedContexts = array();

    /**
     * @var array
     */
    protected static $allContexts;

    /**
     * @var Tx_Contexts_Api_Model_Contexts
     */
    protected static $currentContexts;

    /**
     * @return t3lib_db
     */
    public static function getDb()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * Enter description here ...
     * @param int|string $uidOrAlias
     * @return Tx_Contexts_Context_Abstract
     */
    public static function getContext($uidOrAlias)
    {
        if (array_key_exists($uidOrAlias, self::$loadedContexts)) {
            return self::$loadedContexts[$uidOrAlias];
        }

        foreach (self::$loadedContexts as $context) {
            if ($context->getAlias() === $uidOrAlias || $context->getUid() == $uidOrAlias) {
                return $context;
            }
        }

	    $where = is_numeric($uidOrAlias) ? 'uid = '.$uidOrAlias : "alias = '$uidOrAlias'";
	    $row = self::getDb()->exec_SELECTgetSingleRow('*', 'tx_contexts_contexts', $where.' AND deleted=0');

	    if ($row) {
	        $context = Tx_Contexts_Context_Factory::makeContextInstance($row);
	    } else {
	        $context = null;
	    }

        return self::$loadedContexts[$uidOrAlias] = $context;
    }

    /**
     * @return array
     */
    public static function getContexts()
    {
        if (is_array(self::$allContexts)) {
            return self::$allContexts;
        }

	    $rows = self::getDb()->exec_SELECTgetRows('*', 'tx_contexts_contexts', 'deleted=0');
        self::$allContexts = array();
	    foreach ($rows as $row) {
	        $uid = $row['uid'];
	        $alias = $row['alias'];
	        if (array_key_exists($uid, self::$loadedContexts)) {
                $context = self::$loadedContexts[$uid];
	        } elseif ($alias && array_key_exists($alias, self::$loadedContexts)) {
                $context = self::$loadedContexts[$alias];
	        } else {
	            $context = Tx_Contexts_Context_Factory::makeContextInstance($row);
	            self::$loadedContexts[$uid] = $context;
	        }
	        self::$allContexts[$uid] = $context;
	    }

	    return self::$loadedContexts;
    }

	/**
     * @return Tx_Contexts_Api_Model_Contexts
     */
    public static function getCurrentContexts()
    {
        if (!self::$currentContexts) {
            self::$currentContexts = new Tx_Contexts_Api_Model_Contexts();
        }
        return self::$currentContexts;
    }
}