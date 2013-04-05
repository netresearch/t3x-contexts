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
 * Service for the GetParam Type to register a param and it's value and hook
 * into configArrayPostProc and createHashBase/cHashParamsHook
 *
 * @package    Contexts
 * @subpackage Contexts_Type
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_GetParam_TsfeService
{
    protected static $hooksRegistered = false;

    protected static $params = array();

    /**
     * Add an additional parameter to cHash so that caches are specific
     * to the current context combination.
     *
     * @param array &$params Array of parameters
     * @param null  $ref     Empty reference object
     * @return void
     */
    public function createHashBase(&$params, $ref)
    {
        ksort(self::$params);
        $hashString = serialize(self::$params);
        $key = strtolower(__CLASS__);
        if (isset($params['pA'])) {
            // TYPO3 < 4.7
            $params['pA'][$key] = $hashString;
        } else {
            // TYPO3 >= 4.7
            $params['hashParameters'][$key] = $hashString;
        }
    }

    /**
     * Add current param names to config.linkVars
     *
     * @param array    &$params
     * @param tslib_fe $tsfe
     * @return void
     */
    public function configArrayPostProc(&$params, $tsfe) {
        $linkVars = $params['config']['linkVars'] . ',' . implode(',', array_keys(self::$params));
        $params['config']['linkVars'] = trim($linkVars, ',');
    }

    /**
     * Register a param and it's value and hook into configArrayPostProc and
     * createHashBase/cHashParamsHook
     *
     * @param string $param
     * @param mixed  $value
     * @return void
     */
    public static function register($param, $value) {
        self::$params[$param] = $value;

        if (self::$hooksRegistered) {
            return;
        }

        self::$hooksRegistered = true;
        $conf = &$GLOBALS['TSFE']->TYPO3_CONF_VARS;
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][__CLASS__] =
            '&' . __CLASS__ . '->configArrayPostProc';
        // override page hash generation, TYPO3 version < 4.7
        $conf['SC_OPTIONS']['t3lib/class.t3lib_div.php']['cHashParamsHook'][__CLASS__] =
            '&' . __CLASS__ . '->createHashBase';
        // override page hash generation, TYPO3 version >= 4.7
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][__CLASS__] =
            '&' . __CLASS__ . '->createHashBase';
    }
}
?>
