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
        $httpHeaderName = strtolower(trim($configValue));

        // Get server params from PSR-7 request
        $serverParams = $this->getServerParams();

        // Check, if header exists in HTTP request
        foreach ($serverParams as $header => $value) {
            if (strtolower((string) $header) === $httpHeaderName) {
                // header exists - check if any configured values match
                return $this->invert($this->storeInSession(
                    $this->matchValues((string) $value),
                ));
            }
        }

        // HTTP header does not exist
        return $this->invert(false);
    }

    /**
     * Get server parameters from the current request.
     *
     * @return array<string, mixed> Server parameters
     */
    protected function getServerParams(): array
    {
        // Try to get from PSR-7 request first (preferred in TYPO3 v12+)
        $request = Container::get()->getRequest();
        if ($request instanceof ServerRequestInterface) {
            return $request->getServerParams();
        }

        // Fallback to GLOBALS['TYPO3_REQUEST'] if available
        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($globalRequest instanceof ServerRequestInterface) {
            return $globalRequest->getServerParams();
        }

        // Fallback to $_SERVER
        return $_SERVER;
    }

    /**
     * Checks if the given value is one of the configured allowed values
     *
     * @param string $value Current parameter value
     *
     * @return bool True if the current parameter value is one of the
     *                 configured values
     */
    protected function matchValues(string $value): bool
    {
        $arValues = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values'),
            true,
        );

        // Empty value list, so we allow any value
        if (\count($arValues) === 1 && $arValues[0] === '') {
            return $value !== '';
        }

        $arValues = array_map(trim(...), $arValues);

        return \in_array($value, $arValues, true);
    }
}
