<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Service;

use Netresearch\Contexts\Api\Configuration;

/**
 * Hooks for InstallUtility: Append the necessary field definitions for
 * the enableSettings (tx_contexts_enable, tx_contexts_disable)
 * to the tables they were registered for
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class InstallService
{
    /**
     * A Slot method / Hook that allows to dynamically extend the table definitions for e.g. custom
     * caches. The hook implementation may return table create strings that will be
     * respected by the extension manager during installation of an extension.
     *
     * @param string $strExtKey extension key
     *
     * @return array Either empty array or table create array
     */
    public function appendTableDefinitions(array $arSignalReturn, string $strExtKey): array
    {
        $extensionFlatSettings = Configuration::getExtensionFlatSettings($strExtKey);

        if (!\array_key_exists($strExtKey, $extensionFlatSettings)) {
            return [];
        }

        $sql = '';
        foreach ($extensionFlatSettings[$strExtKey] as $table => $settings) {
            $sql .= "\nCREATE TABLE {$table} (\n";

            $arSql = [];
            foreach ($settings as $setting) {
                if (\is_array($setting)) {
                    continue;
                }
                $flatColumns = Configuration::getFlatColumns($table, $setting);
                $arSql[] = $flatColumns[0] . ' tinytext';
                $arSql[] = $flatColumns[1] . ' tinytext';
            }

            $sql .= implode(",\n", $arSql);
            $sql .= ');';
        }

        return ['sqlString' => [$sql], 'extensionKey' => $strExtKey];
    }
}
