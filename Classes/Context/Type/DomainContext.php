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
 * Matches on the current domain name
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class DomainContext extends AbstractContext
{
    /**
     *
     */
    public function match(array $arDependencies = []): bool
    {
        $curHost = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $arDomains = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_domains'),
            true,
        );

        foreach ($arDomains as $domain) {
            if ($this->matchDomain($domain, $curHost)) {
                return $this->invert(true);
            }
        }

        return $this->invert(false);
    }

    /**
     *
     */
    protected function matchDomain(string $domain, string $curHost): bool
    {
        if ($domain === '') {
            return false;
        }

        if ($domain[0] !== '.') {
            return $domain === $curHost;
        }

        return substr($domain, 1) === $curHost
            || substr($curHost, -\strlen($domain) + 1) === substr($domain, 1);
    }
}
