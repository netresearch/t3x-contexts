<?php
declare(encoding = 'UTF-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Load context by alias.
 * Caches results.
 *
 * @package    Contexts
 * @subpackage Api
 * @author     André Hähnel <andre.haehnel@netresearch.de>
 */
class Tx_Contexts_Api_ContextMatcher
{
    /**
     * Singleton instance
     *
     * @var Tx_Contexts_Api_ContextMatcher
     */
    protected static $instance;

    /**
     * Database table name with contexts
     *
     * @var string
     */
    protected $strTable = 'tx_contexts_contexts';

    /**
     * Match results. Alias => boolean match result
     *
     * @var array
     */
    protected $arMatches = array();


    /**
     * Singleton
     *
     * @return self One instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Unsets this instance
     *
     * @return void
     */
    public static function clearInstance()
    {
        self::$instance = null;
    }

    /**
     * Match context by alias
     *
     * @param string $strContext alias from context entry
     *
     * @return boolean TRUE if context matches, FALSE if not
     *
     * @throws \Exception Database error or context not found
     */
    public function matches($strContext)
    {
        if (isset($this->arMatches[$strContext])) {
            return $this->arMatches[$strContext];
        }

        $arRow = $this->getContextRow($strContext);
        try {
            $context = Tx_Contexts_Context_Factory::createFromDb($arRow);
            if ($context) {
                $this->arMatches[$strContext] = (bool) $context->match();
            } else {
                $this->arMatches[$strContext] = true;
            }
        } catch (\Exception $e) {
            $this->arMatches[$strContext] = false;
        }

        return $this->arMatches[$strContext];
    }

    /**
     * Load context from database
     *
     * @param string $strContext alias from context entry
     *
     * @return array database result
     *
     * @throws \Exception Database error or context not found
     */
    protected function getContextRow($strContext)
    {
        /* @var $TYPO3_DB \TYPO3\CMS\Core\Database\DatabaseConnection */
        global $TYPO3_DB;

        $strWhere = 'deleted = 0 AND disabled = 0'
            . ' AND alias =' . $TYPO3_DB->fullQuoteStr($strContext, $this->strTable);

        $arRow = $TYPO3_DB->exec_SELECTgetSingleRow(
            '*', $this->strTable, $strWhere
        );

        //sql error
        if ($arRow === null) {
            throw new \Exception($TYPO3_DB->sql_error());
        }

        //Nothing found
        if ($arRow === false) {
            throw new \Exception('Context does not exist: ' . $strContext);
        }

        return $arRow;
    }
}
?>
