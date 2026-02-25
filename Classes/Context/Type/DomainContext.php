<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Psr\Http\Message\ServerRequestInterface;
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
        $curHost = $this->getCurrentHost();
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
    /**
     * Get the current host from the request.
     *
     * @return string The current host
     */
    protected function getCurrentHost(): string
    {
        // Try to get from PSR-7 request first (preferred in TYPO3 v12+)
        $request = Container::get()->getRequest();
        if ($request instanceof ServerRequestInterface) {
            return $request->getUri()->getHost();
        }

        // Fallback to GLOBALS['TYPO3_REQUEST'] if available
        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($globalRequest instanceof ServerRequestInterface) {
            return $globalRequest->getUri()->getHost();
        }

        // Fallback to $_SERVER
        return (string) ($_SERVER['HTTP_HOST'] ?? '');
    }

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
