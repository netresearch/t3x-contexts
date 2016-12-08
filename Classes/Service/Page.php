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

if (isset($TYPO3_CONF_VARS['SYS']['compat_version'])
    && t3lib_div::int_from_ver($TYPO3_CONF_VARS['SYS']['compat_version']) < 6002000
) {
    //compatibility with TYPO3 < 6.2, see http://forge.typo3.org/issues/50881
    require_once PATH_tslib . 'interfaces/interface.tslib_menu_filterMenuPagesHook.php';
}

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
     * @param t3lib_pageSelect $ref    Object that calls the hook     *
     * @return string SQL command part that gets added to the query
     */
    public function enableFields($params, $ref)
    {
        return $this->getFilterSql($params['table']);
    }

    /**
     * Add page access restrictions through context settings.
     *
     * @param integer          &$uid                     The page ID
     * @param boolean          &$disableGroupAccessCheck If set, the check for
     *                                                   group access is disabled.
     *                                                   VERY rarely used
     * @param t3lib_pageSelect $pObj                     t3lib_pageSelect object
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
     * @return string SQL filter string beginning with " AND "
     */
    protected function getFilterSql($table) {
        global $TCA;
        $sql = '';

        foreach (Tx_Contexts_Api_Configuration::getEnableSettings($table) as $setting) {
            $flatColumns = Tx_Contexts_Api_Configuration::getFlatColumns($table, $setting);
            if (!$flatColumns) {
                t3lib_div::devLog(
                    'Missing flat columns for setting "' . $setting . '"',
                    'tx_contexts',
                    2,
                    array('table' => $table)
                );
                continue;
            }

            $enableChecks = array(
                $flatColumns[1] . " IS NULL",
                $flatColumns[1] . " = ''"
            );
            $disableChecks = array();

            foreach (Tx_Contexts_Context_Container::get() as $context) {
                /* @var $context Tx_Contexts_Context_Abstract */
                $enableChecks[] = $GLOBALS['TYPO3_DB']->listQuery(
                    $flatColumns[1], $context->getUid(), $table
                );
                $disableChecks[] = 'NOT ' . $GLOBALS['TYPO3_DB']->listQuery(
                    $flatColumns[0], $context->getUid(), $table
                );
            }

            $sql = ' AND (' . implode(' OR ', $enableChecks) . ')';
            if (count($disableChecks)) {
                $sql .= ' AND ('
                    . $flatColumns[0] . " IS NULL"
                    . ' OR ' . $flatColumns[0] . " = ''"
                    . ' OR (' . implode(' AND ', $disableChecks) . ')' .
                    ')';
            }
        }

        return $sql;
    }

    /**
     * Modify the cache hash
     *
     * @param array &$params Array of parameters: hashParameters,
     *                       createLockHashBase
     * @param null  $ref     Reference object
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
     * @param array &$data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param boolean $spacer If set, then the page is a spacer.
     * @param \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject $obj The menu object
     * @return boolean Returns TRUE if the page can be safely included.
     */
    public function processFilter(
        array &$data, array $banUidArray, $spacer, tslib_menu $obj
    ) {
        return
        Tx_Contexts_Api_Record::isEnabled('pages', $data) &&
        Tx_Contexts_Api_Record::isSettingEnabled('pages', 'tx_contexts_nav', $data);
    }

    /**
     * Checks if a page is OK to include in the final menu item array.
     * this method is be called from typo < 4.6
     *
     * @param array &$data Array of menu items
     * @param array $banUidArray Array of page uids which are to be excluded
     * @param boolean $spacer If set, then the page is a spacer.
     * @param \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject $obj The menu object
     * @return boolean Returns TRUE if the page can be safely included.
     */
    public function tslib_menu_filterMenuPagesHook(
        array &$data, array $banUidArray, $spacer, tslib_menu $obj
    ) {
        return $this->processFilter($data, $banUidArray, $spacer, $obj);
    }
}
?>