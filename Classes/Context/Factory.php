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
 * Context factory
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Tx_Contexts_Context_Factory
{
    /**
     * Find the right class for the context type and instantiate it
     *
     * @param array $arRow Database context row
     *
     * @return Tx_Contexts_Context_Abstract|null
     * @throws Tx_Contexts_Exception
     */
    public static function createFromDb($arRow)
    {
        $classMap = Tx_Contexts_Api_Configuration::getContextTypes();
        $type     = $arRow['type'];

        if (!$type || !array_key_exists($type, $classMap)) {
            t3lib_div::devLog(
                'No class found for context type "' . $type . '"',
                'tx_contexts', 2
            );
            $type = 'default';
        }

        if (!isset($classMap[$type]['class'])) {
            return null;
        }
        $class = $classMap[$type]['class'];
        if (!$class) {
            return null;
        }

        $instance = t3lib_div::makeInstance($class, $arRow);
        if ($instance instanceof t3lib_Singleton) {
            throw new Tx_Contexts_Exception($class . ' may not be singleton');
        }
        if (!$instance instanceof Tx_Contexts_Context_Abstract) {
            throw new Tx_Contexts_Exception(
                $class . ' must extend Tx_Contexts_Context_Abstract'
            );
        }
        return $instance;
    }
}
?>