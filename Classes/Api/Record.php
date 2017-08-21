<?php

namespace Netresearch\Contexts\Api;

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

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * API with methods to retrieve context information for records.
 *
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Record
{
    /**
     * Determines if the specified record is enabled or disabled by the current
     * contexts (means that the records is disabled if one of the enableSettings
     * are disabled for one of the current contexts).
     *
     * @param string    $table Table name
     * @param array|int $row   Record array or an uid
     *
     * @return bool
     */
    public static function isEnabled($table, $row)
    {
        $enableSettings = Configuration::getEnableSettings($table);
        if (!$enableSettings) {
            return true;
        }
        foreach ($enableSettings as $setting) {
            if (!self::isSettingEnabled($table, $setting, $row)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if a setting is enabled or disabled by the current contexts
     * (returns false if the setting is disabled for one of the contexts).
     *
     * @param string    $table   Table name
     * @param string    $setting Setting name
     * @param array|int $row     Record array or an uid
     *
     * @return bool
     */
    public static function isSettingEnabled($table, $setting, $row)
    {
        if (is_array($row)) {
            $enabledFlat = self::isSettingEnabledFlat($table, $setting, $row);

            if ($enabledFlat !== null) {
                return $enabledFlat;
            }

            if (!isset($row['uid'])) {
                GeneralUtility::devLog(
                    'Missing uid field in row',
                    'tx_contexts',
                    GeneralUtility::SYSLOG_SEVERITY_WARNING,
                    ['table' => $table, 'row' => $row]
                );

                return false;
            }

            $uid = (int) $row['uid'];
        } else {
            $uid = (int) $row;
        }

        /* @var $context AbstractContext */
        foreach (Container::get() as $context) {
            $rowSetting = $context->getSetting($table, $setting, $uid);
            $defaultSetting = $context->getSetting($table, $setting, 0);

            if (($rowSetting && !$rowSetting->getEnabled())
                || ($defaultSetting && !$defaultSetting->getEnabled())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tries to get if the setting is enabled by evaluating the flat columns
     * within the record.
     *
     * @param string $table   Table name
     * @param string $setting Setting name
     * @param array  $row     Record array
     *
     * @return null|bool NULL when table has no flat settings or the record
     *                   doesn't contain the appropriate flat columns
     *                   boolean otherwise
     */
    protected static function isSettingEnabledFlat($table, $setting, array $row)
    {
        $flatColumns
            = Configuration::getFlatColumns($table, $setting);

        if (!$flatColumns) {
            return;
        }

        $rowValid = true;
        $flatColumnContents = [];

        foreach ($flatColumns as $i => $flatColumn) {
            if (!array_key_exists($flatColumn, $row)) {
                GeneralUtility::devLog(
                    'Missing flat field "'.$flatColumn.'"',
                    'tx_contexts',
                    GeneralUtility::SYSLOG_SEVERITY_WARNING,
                    ['table' => $table, 'row' => $row]
                );
                $rowValid = false;
            } elseif ($row[$flatColumn] !== '') {
                $flatColumnContents[$i]
                    = array_flip(explode(',', $row[$flatColumn]));
            } else {
                $flatColumnContents[$i] = [];
            }
        }

        if (!$rowValid) {
            return;
        }

        foreach (Container::get() as $context) {
            if (array_key_exists($context->getUid(), $flatColumnContents[0])) {
                return false;
            }
        }

        return true;
    }
}
