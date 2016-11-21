<?php
namespace Netresearch\Contexts\Context\Type;

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
use Netresearch\Contexts\Context\AbstractContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches on the current frontend user IP
 *
 * @author     Andre Hähnel <andre.haehnel@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class IpContext extends AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     */
    public function match(array $arDependencies = array())
    {
        $strCurIp = $_SERVER['REMOTE_ADDR'];

        $bIpv4 = filter_var(
            $strCurIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
        ) !== false;
        $bIpv6 = filter_var(
            $strCurIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6
        ) !== false;

        if (!$bIpv4 && !$bIpv6) {
            //invalid IP
            return $this->invert(false);
        }

        $arIpRange = explode("\n", trim($this->getConfValue('field_ip')));

        if (count($arIpRange) == 1 && $arIpRange[0] == '') {
            return $this->invert(false);
        }

        $strRange = implode(',', $arIpRange);
        return $this->invert($this->isIpInRange($strCurIp, $bIpv4, $strRange));
    }

    /**
     * Check if the remote IP is the allowed range.
     * Supports IPv4 and IPv6.
     *
     * @param string  $strIp    remote IP address
     * @param bool $bIpv4    If the IP is IPv4 (if not, it's IPv6)
     * @param string  $strRange Defined range. Comma-separated list of IPs.
     *                          * supported for parts of the address.
     *
     * @return bool True if the IP is in the range
     */
    protected function isIpInRange($strIp, $bIpv4, $strRange)
    {
        if ($bIpv4) {
            return GeneralUtility::cmpIPv4($strIp, $strRange);
        } else {
            return GeneralUtility::cmpIPv6($strIp, $strRange);
        }
    }
}
