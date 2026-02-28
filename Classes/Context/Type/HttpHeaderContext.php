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
 * Matches on an HTTP header with a certain value
 *
 * @author  Michael Schams <schams.net>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class HttpHeaderContext extends AbstractContext
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
        // Determine which HTTP header has been configured
        $configValue = $this->getConfValue('field_name');
        $httpHeaderName = trim($configValue);

        if ($httpHeaderName === '') {
            return $this->invert(false);
        }

        // Try PSR-7 header lookup first (supports standard header names like "User-Agent")
        $headerValue = $this->getHeaderValue($httpHeaderName);

        if ($headerValue !== null) {
            return $this->invert($this->storeInSession(
                $this->matchValues($headerValue),
            ));
        }

        // HTTP header does not exist
        return $this->invert(false);
    }

    /**
     * Get the value of an HTTP header from the current request.
     *
     * Supports both standard HTTP header names (e.g., "User-Agent") and
     * $_SERVER key format (e.g., "HTTP_USER_AGENT") for backward compatibility.
     *
     * @param string $headerName The header name to look up
     *
     * @return string|null The header value, or null if the header doesn't exist
     */
    protected function getHeaderValue(string $headerName): ?string
    {
        $request = $this->getRequest();

        if ($request instanceof ServerRequestInterface) {
            // Try PSR-7 header lookup first (standard HTTP header names)
            if ($request->hasHeader($headerName)) {
                return $request->getHeaderLine($headerName);
            }

            // Fall back to server params ($_SERVER key format)
            /** @var array<string, mixed> $serverParams */
            $serverParams = $request->getServerParams();

            return $this->findInServerParams($serverParams, $headerName);
        }

        // Ultimate fallback to $_SERVER
        /** @var array<string, mixed> $fallbackParams */
        $fallbackParams = $_SERVER;

        return $this->findInServerParams($fallbackParams, $headerName);
    }

    /**
     * Get the PSR-7 request from the Container or TYPO3_REQUEST global.
     */
    protected function getRequest(): ?ServerRequestInterface
    {
        $request = Container::get()->getRequest();
        if ($request instanceof ServerRequestInterface) {
            return $request;
        }

        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($globalRequest instanceof ServerRequestInterface) {
            return $globalRequest;
        }

        return null;
    }

    /**
     * Find a header value in server params by case-insensitive key matching.
     *
     * @param array<string, mixed> $serverParams Server parameters ($_SERVER style)
     * @param string               $headerName   Header name to find
     *
     * @return string|null The header value, or null if not found
     */
    protected function findInServerParams(array $serverParams, string $headerName): ?string
    {
        $lowerName = strtolower($headerName);

        foreach ($serverParams as $key => $value) {
            if (strtolower((string) $key) === $lowerName) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Checks if the actual header value contains any of the configured values
     * as a substring (case-insensitive).
     *
     * Each line in the configuration is treated as a pattern. The match succeeds
     * if any pattern is found as a substring of the actual header value.
     * This enables matching complex values like User-Agent strings against
     * simple patterns like "iPhone" or "Mobile".
     *
     * @param string $value Current header value
     *
     * @return bool True if the current header value contains any configured pattern
     */
    protected function matchValues(string $value): bool
    {
        $arValues = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values'),
            true,
        );

        if (\count($arValues) === 0) {
            return $value !== '';
        }

        $arValues = array_map(trim(...), $arValues);

        foreach ($arValues as $pattern) {
            if ($pattern === '') {
                continue;
            }

            if (str_contains(strtolower($value), strtolower($pattern))) {
                return true;
            }
        }

        return false;
    }
}
