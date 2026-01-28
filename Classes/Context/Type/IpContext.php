<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches on the current frontend user IP
 *
 * @author  Andre Hähnel <andre.haehnel@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
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
    public function match(array $arDependencies = []): bool
    {
        $strCurIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        $bIpv4 = filter_var(
            $strCurIp,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_IPV4,
        ) !== false;
        $bIpv6 = filter_var(
            $strCurIp,
            \FILTER_VALIDATE_IP,
            \FILTER_FLAG_IPV6,
        ) !== false;

        if (!$bIpv4 && !$bIpv6) {
            // invalid IP
            return $this->invert(false);
        }

        $arIpRange = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_ip'),
            true,
        );

        if (\count($arIpRange) === 1 && $arIpRange[0] === '') {
            return $this->invert(false);
        }

        $strRange = implode(',', $arIpRange);

        return $this->invert($this->isIpInRange($strCurIp, $bIpv4, $strRange));
    }

    /**
     * Check if the remote IP is the allowed range.
     * Supports IPv4 and IPv6.
     *
     * @param string $strIp     remote IP address
     * @param bool   $bIpv4     If the IP is IPv4 (if not, it's IPv6)
     * @param string $strRange  Defined range. Comma-separated list of IPs.
     *                          * supported for parts of the address.
     *
     * @return bool True if the IP is in the range
     */
    protected function isIpInRange(string $strIp, bool $bIpv4, string $strRange): bool
    {
        if ($bIpv4) {
            return GeneralUtility::cmpIPv4($strIp, $strRange);
        }

        return GeneralUtility::cmpIPv6($strIp, $strRange);
    }
}
