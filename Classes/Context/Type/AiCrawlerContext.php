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

use Netresearch\Contexts\Context\Type\AiCrawler\AiCrawlerUserAgents;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches requests whose User-Agent header names a known AI crawler or AI
 * fetcher (see AiCrawlerUserAgents), or one of the additional substrings
 * configured in the context record.
 *
 * The User-Agent header is set by the client, so this context detects what a
 * client claims to be. It does not verify the claim.
 *
 * @license AGPL-3.0-or-later
 *
 * @see    https://www.netresearch.de
 */
class AiCrawlerContext extends HttpHeaderContext
{
    /**
     * Check if the context is active now.
     *
     * Like the HTTP header context, the header is evaluated on every request
     * and the result is never kept in the session.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     */
    public function match(array $arDependencies = []): bool
    {
        // PSR-7 header name first; the $_SERVER key covers requests whose
        // header is only available in the server parameters.
        $userAgent = $this->getHeaderValue('User-Agent') ?? $this->getHeaderValue('HTTP_USER_AGENT');

        if ($userAgent === null || $userAgent === '') {
            return $this->invert(false);
        }

        return $this->invert($this->containsAnyPattern($userAgent, $this->getPatterns()));
    }

    /**
     * The built-in and globally configured tokens plus the additional
     * substrings of this context record.
     *
     * @return list<string>
     */
    protected function getPatterns(): array
    {
        $additional = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_additional_user_agents'),
            true,
        );

        return [...AiCrawlerUserAgents::getAll(), ...$additional];
    }
}
