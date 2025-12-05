<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Api;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;

/**
 * API with methods to retrieve context information for records
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class Record
{
    /**
     * Determines if the specified record is enabled or disabled by the current
     * contexts (means that the records is disabled if one of the enableSettings
     * are disabled for one of the current contexts)
     *
     * @param string $table Table name
     * @param array  $row   Record array or an uid
     *
     *
     * @throws DBALException
     * @throws Exception
     */
    public static function isEnabled(string $table, array $row): bool
    {
        $enableSettings = Configuration::getEnableSettings($table);

        if (\count($enableSettings) === 0) {
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
     * (returns false if the setting is disabled for one of the contexts)
     *
     * @param string $table   Table name
     * @param string $setting Setting name
     * @param array  $row     Record array
     *
     *
     * @throws DBALException
     * @throws Exception
     */
    public static function isSettingEnabled(string $table, string $setting, array $row): bool
    {
        $enabledFlat = self::isSettingEnabledFlat($table, $setting, $row);

        if ($enabledFlat !== null) {
            return $enabledFlat;
        }

        if (!isset($row['uid'])) {
            return false;
        }

        $uid = (int) $row['uid'];

        /** @var AbstractContext $context */
        foreach (Container::get() as $context) {
            $rowSetting = $context->getSetting($table, $setting, $uid);
            $defaultSetting = $context->getSetting($table, $setting, 0);

            if (
                (($rowSetting !== null) && !$rowSetting->getEnabled())
                || (($defaultSetting !== null) && !$defaultSetting->getEnabled())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Tries to get if the setting is enabled by evaluating the flat columns
     * within the record
     *
     * @param string $table   Table name
     * @param string $setting Setting name
     * @param array  $row     Record array
     *
     * @return bool|null NULL when table has no flat settings or the record
     *                   doesn't contain the appropriate flat columns
     *                   boolean otherwise
     */
    protected static function isSettingEnabledFlat(string $table, string $setting, array $row): ?bool
    {
        $flatColumns = Configuration::getFlatColumns($table, $setting);

        if (\count($flatColumns) === 0) {
            return null;
        }

        $rowValid = true;
        $flatColumnContents = [];

        foreach ($flatColumns as $i => $flatColumn) {
            if (!\array_key_exists($flatColumn, $row) || ($row[$flatColumn] === null)) {
                $rowValid = false;
            } elseif ($row[$flatColumn] !== '') {
                $flatColumnContents[$i] = array_flip(explode(',', (string) $row[$flatColumn]));
            } else {
                $flatColumnContents[$i] = [];
            }
        }

        if (!$rowValid) {
            return null;
        }

        foreach (Container::get() as $context) {
            if (\array_key_exists($context->getUid(), $flatColumnContents[0])) {
                return false;
            }
        }

        return true;
    }
}
