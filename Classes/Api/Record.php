<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
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
 * API with methods to retrieve context information for records
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Tx_Contexts_Api_Record {
    /**
     * Determines if the specified record is enabled or disabled by the current
     * contexts (means that the records is disabled if one of the enableField
     * settings are disabled for one of the current contexts)
     *
     * @param string    $table
     * @param array|int $row The record array or an uid
     * @return boolean
     */
    public static function isEnabled($table, $row) {
        global $TCA;
        $enableFields = Tx_Contexts_Api_Configuration::getTcaCtrlEnablecolumns($table);
        if (!$enableFields) {
            return true;
        }
        foreach ($enableFields as $field) {
            if (!self::isSettingEnabled($table, $field, $row)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Determines if a setting is enabled or disabled by the current contexts
     * (returns false if the setting is disabled for one of the contexts)
     *
     * @param string       $table
     * @param string       $field
     * @param array|string $row
     * @return boolean
     */
    public static function isSettingEnabled($table, $field, $row) {
        if (is_array($row)) {
            $enabledFlat = self::isSettingEnabledFlat($table, $field, $row);
            if ($enabledFlat !== null) {
                return $enabledFlat;
            }
            if (!isset($row['uid'])) {
                t3lib_div::devLog(
                    'Missing uid field in row',
                    'tx_contexts',
                    2,
                    array('table' => $table, 'row' => $row)
                );
                return false;
            }
            $uid = (int) $row['uid'];
        } else {
            $uid = (int) $row;
        }

        foreach (Tx_Contexts_Context_Container::get() as $context) {
            /* @var $context Tx_Contexts_Context_Abstract */
            $rowSetting = $context->getSetting($table, $field, $uid);
            $defaultSetting = $context->getSetting($table, $field, 0);
            if (
                $rowSetting && !$rowSetting->getEnabled() ||
                $defaultSetting && !$defaultSetting->getEnabled()
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tries to get if the setting is enabled by evaluating the flattened fields
     * within the record
     *
     * @param string $table
     * @param string $field
     * @param array  $row
     * @return NULL|boolean NULL when table has no flat settings or the record
     *                      doesn't contain the appropriate flat fields
     *                      boolean otherwise
     */
    protected static function isSettingEnabledFlat($table, $field, array $row) {
        $flatFields = Tx_Contexts_Api_Configuration::getFlatFields($table, $field);
        if (!$flatFields) {
            return null;
        }

        $rowValid = true;
        $flatFieldContents = array();
        foreach ($flatFields as $i => $flatField) {
            if (!array_key_exists($flatField, $row)) {
                t3lib_div::devLog(
                    'Missing flat field "' . $flatField . '"',
                    'tx_contexts',
                    2,
                    array('table' => $table, 'row' => $row)
                );
                $rowValid = false;
            } elseif ($row[$flatField] !== '') {
                $flatFieldContents[$i] =  array_flip(explode(',', $row[$flatField]));
            } else {
                $flatFieldContents[$i] = array();
            }
        }

        if (!$rowValid) {
            return null;
        }

        foreach (Tx_Contexts_Context_Container::get() as $context) {
            if (array_key_exists($context->getUid(), $flatFieldContents[0])) {
                return false;
            }
        }

        return true;
    }
}
?>