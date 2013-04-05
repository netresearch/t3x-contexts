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
 * Matches on the current frontend user IP
 *
 * @package    Contexts
 * @subpackage Contexts_Type
 * @author     Andre Hähnel <andre.haehnel@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_Ip extends Tx_Contexts_Context_Abstract
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return boolean True if the context is active, false if not
     */
    public function match(array $arDependencies = array())
    {
       $strCurIp = $_SERVER['REMOTE_ADDR'];

       $arIpRange = explode("\n", $this->getConfValue('field_ip'));

       if (count($arIpRange)) {
           $strRange = implode(',', $arIpRange);
           return $this->isIpInRange($strCurIp, $strRange);
       }

       return false;

    }
    /**
     * Check if the remote ip in range
     * switch IPv4 and IPv6
     *
     * @param string $strIp   remote ip number
     * @param string $arRange defined range
     * @return boolean
     */
    protected function isIpInRange($strIp, $arRange)
    {

        if(filter_var($strIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return t3lib_div::cmpIPv4($strIp, $arRange);

        }

        if(filter_var($strIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return t3lib_div::cmpIPv6($strIp, $arRange);

        }

        return false;
    }

}
?>
