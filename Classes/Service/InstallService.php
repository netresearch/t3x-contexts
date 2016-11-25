<?php
namespace Netresearch\Contexts\Service;

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
use Netresearch\Contexts\Api\Configuration;
/**
 * Hooks for InstallUtility: Append the necessary field definitions for
 * the enableSettings (tx_contexts_enable, tx_contexts_disable)
 * to the tables they were registered for
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class InstallService
{
    /**
     * A Slot method / Hook that allows to dynamically extend the table definitions for e.g. custom
     * caches. The hook implementation may return table create strings that will be
     * respected by the extension manager during installation of an extension.
     *
     * @param array  $arSignalReturn
     * @param string $strExtKey      extension key
     *
     * @return array Either empty array or table create array
     */
    public function appendTableDefinitions($arSignalReturn, $strExtKey)
    {
        global $TCA;

        $extensionFlatSettings = Configuration::getExtensionFlatSettings($strExtKey);

        if (!array_key_exists($strExtKey, $extensionFlatSettings)) {
            return array();
        }

        $sql = '';
        foreach ($extensionFlatSettings[$strExtKey] as $table => $settings) {
            $sql .= "\nCREATE TABLE $table (\n";

            foreach ($settings as $setting) {
                if (is_array($setting)) {
                    continue;
                }
                $flatColumns = Configuration::getFlatColumns($table, $setting);
                $sql .= $flatColumns[0] . " tinytext,\n";
                $sql .= $flatColumns[1] . " tinytext\n";
            }
            $sql .= ');';
        }
        return array('sqlString' => array($sql), 'extensionKey' => $strExtKey);
    }
}
