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

/**
 * Hook into enableFields() to hide pages and elements that are
 * may not be shown for the current contexts.
 *
 * @package Contexts
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Service_Page
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
        $table = $params['table'];
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
            "tx_contexts_enable = ''",
            "tx_contexts_enable IS NULL"
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
        $sql .= ' AND ('
            . "tx_contexts_disable = ''"
            . ' OR tx_contexts_disable IS NULL'
            . ' OR (' . implode(' AND ', $disableChecks) . ')'
            . ')';

        return $sql;
    }
}
?>
