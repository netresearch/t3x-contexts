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
     * @param array $row
     * @throws Tx_Contexts_Exception
     * @return Tx_Contexts_Context_Abstract|null
     */
    public static function makeContextInstance($row)
    {
        $classMap = Tx_Contexts_Api_Configuration::getContextTypes();
        $type = $row['type'];
        if (!$type || !array_key_exists($type, $classMap)) {
            t3lib_div::devLog('No class found for context type "' . $type . '"', 'tx_contexts', 2);
            $type = 'default';
        }

        if (!isset($classMap[$type]['class'])) {
            return null;
        }
        $class = $classMap[$type]['class'];
        if (!$class) {
            return null;
        }

        if (strpos($class, ':') !== FALSE) {
            list($file, $class) = t3lib_div::revExplode(':', $class, 2);
            if ($class[0] == '&') {
                throw new Tx_Contexts_Exception(
                    'Persistence feature is not supported (remove & from ' . $class . ')'
                );
            }
            $requireFile = t3lib_div::getFileAbsFileName($file);
            if ($requireFile) {
                t3lib_div::requireOnce($requireFile);
            }
        }

        $instance = t3lib_div::makeInstance($class, $row);
        if ($instance instanceof t3lib_Singleton) {
            throw new Tx_Contexts_Exception($class . ' may not be singleton');
        }
        if (!$instance instanceof Tx_Contexts_Context_Abstract) {
            throw new Tx_Contexts_Exception($class . ' must extend Tx_Contexts_Context_Abstract');
        }
        return $instance;
    }
}
?>