<?php

namespace Netresearch\Contexts\Context\Type;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Netresearch\Contexts\Context\AbstractContext;

/**
 * Matches on the current domain name.
 */
class DomainContext extends AbstractContext
{
    public function match(array $arDependencies = [])
    {
        $curHost = $_SERVER['HTTP_HOST'];
        $arDomains = explode("\n", $this->getConfValue('field_domains'));

        foreach ($arDomains as $domain) {
            if ($this->matchDomain($domain, $curHost)) {
                return $this->invert(true);
            }
        }

        return $this->invert(false);
    }

    protected function matchDomain($domain, $curHost)
    {
        if ($domain[0] != '.') {
            if ($domain == $curHost) {
                return true;
            }

            return false;
        }

        if (substr($domain, 1) == $curHost
            || substr($curHost, -strlen($domain) + 1) == substr($domain, 1)
        ) {
            return true;
        }

        return false;
    }
}
