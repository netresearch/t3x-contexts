<?php
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

require_once PATH_tslib . 'interfaces/interface.tslib_menu_filterMenuPagesHook.php';

/**
 * Hook into enableFields() to hide pages and elements that are
 * may not be shown for the current contexts.
 *
 * @package Contexts
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Service_Page
    implements t3lib_pageSelect_getPageHook, tslib_menu_filterMenuPagesHook
{
    /**
     * Add context filtering to an SQL query
     *
     * @param array            $params Array of parameters:
     *                                 - table        - table name
     *                                 - show_hidden  - if hidden elements shall
     *                                                  be shown
     *                                 - ignore_array - enable field names which
     *                                                  should not be used
     *                                 - ctrl         - TCA table control data
     * @param t3lib_pageSelect $ref    Object that calls the hook
     *
     * @return string SQL command part that gets added to the query
     */
    public function enableFields($params, $ref)
    {
        return $this->getFilterSql($params['table']);
    }

    /**
     * Add page access restrictions through context settings.
     *
     * @return void
     */
	public function getPage_preProcess(
        &$uid, &$disableGroupAccessCheck, t3lib_pageSelect $pObj
    ) {
        static $done = false;
        if ($done) {
            return;
        }
        $pObj->where_groupAccess .= $this->getFilterSql('pages');
        $done = true;
    }

    /**
     * Generates a SQL WHERE statement that filters out records
     * that may not be accessed with the current context settings
     *
     * @param string $table Database table name
     *
     * @return string SQL filter string beginning with " AND "
     */
    protected function getFilterSql($table)
    {
        $enableFields = Tx_Contexts_Api_Configuration
            ::getEnableFieldsExtensions();
        if (!array_key_exists($table, $enableFields)) {
            return '';
        }
        if (!in_array('contexts', $enableFields[$table])) {
            return '';
        }

        $arContextIds = array();
        foreach (Tx_Contexts_Context_Container::get() as $context) {
            /* @var $context Tx_Contexts_Context_Abstract */
            $arContextIds[] = $context->getUid();
        }

        $enableChecks = array(
            "tx_contexts_enable = ''"
        );
        $disableChecks = array();

        foreach ($arContextIds as $id) {
            $enableChecks[] = $GLOBALS['TYPO3_DB']->listQuery(
                'tx_contexts_enable', $id, $table
            );
            $disableChecks[] = 'NOT ' . $GLOBALS['TYPO3_DB']->listQuery(
                'tx_contexts_disable', $id, $table
            );
        }

        $sql = ' AND (' . implode(' OR ', $enableChecks) . ')';
        if (count($disableChecks)) {
            $sql .= ' AND (' . "tx_contexts_disable = ''"
                . ' OR (' . implode(' AND ', $disableChecks) . ')'
                . ')';
        }

        return $sql;
    }

    /**
     * Add an additional parameter to cHash so that caches are specific
     * to the current context combination.
     *
     * We modify the 'pA' array since it contains the parameters that get used
     * to build the hash.
     *
     * Used by TYPO3 versions < 4.7
     *
     * @param array &$params Array of parameters: addQueryParams, params, pA
     * @param null  $ref     Empty reference object
     *
     * @return void
     *
     * @usedby t3lib_div::cHashParams()
     */
    public function cHashParams(&$params, $ref)
    {
        $params['pA']['tx_contexts-contexts'] = $this->getHashString();
    }

    /**
     * Modify the cache hash in TYPO3 version >= 4.7
     *
     * @param array &$params Array of parameters: hashParameters,
     *                       createLockHashBase
     * @param null  $ref     Reference object
     *
     * @return void
     */
    public function createHashBase(&$params, $ref)
    {
        $params['hashParameters']['tx_contexts-contexts']
            = $this->getHashString();
    }

    /**
     * Creates a string that can be used to identify the current
     * context combination.
     * Used for cache hash modification.
     *
     * @return string Hash modificator
     */
    protected function getHashString()
    {
        $keys = array_keys(
            Tx_Contexts_Context_Container::get()->getArrayCopy()
        );
        sort($keys, SORT_NUMERIC);
        return implode(',', $keys);
    }

    /**
     * Checks if a page is OK to include in the final menu item array.
     *
     * @param array $data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param boolean $spacer If set, then the page is a spacer.
     * @param \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject $obj The menu object
     * @return boolean Returns TRUE if the page can be safely included.
     */
    public function processFilter(
        array &$data, array $banUidArray, $spacer, tslib_menu $obj
    ) {
        if ($data['tx_contexts_nav_enable'] == ''
            && $data['tx_contexts_nav_disable'] == ''
        ) {
            return true;
        }

        $contexts = Tx_Contexts_Context_Container::get();
        if ($data['tx_contexts_nav_disable'] != '') {
            $arDisabledFor = explode(',', $data['tx_contexts_nav_disable']);
            foreach ($arDisabledFor as $id) {
                if (array_key_exists($id, $contexts)) {
                    return false;
                }
            }
            if ($data['tx_contexts_nav_enable'] == '') {
                return true;
            }
        }

        $arEnabledFor = explode(',', $data['tx_contexts_nav_enable']);
        foreach ($arEnabledFor as $id) {
            if (array_key_exists($id, $contexts)) {
                return true;
            }
        }
        return false;
    }
}
?>
