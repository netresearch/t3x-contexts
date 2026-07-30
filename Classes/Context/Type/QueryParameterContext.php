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
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches on a GET parameter with a certain value
 *
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class QueryParameterContext extends AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     *
     * @throws RuntimeException
     */
    public function match(array $arDependencies = []): bool
    {
        $param = $this->getParameterName();

        if ($param === '') {
            throw new RuntimeException(
                'Parameter name missing from GET Parameter context configuration',
                3404190205,
            );
        }

        $queryParams = $this->getQueryParams();

        if (!\array_key_exists($param, $queryParams)) {
            // load from session if no param given
            [$bUseMatch, $bMatch] = $this->getMatchFromSession();
            return $this->invert($bUseMatch && ($bMatch ?? false));
        }

        $value = $this->getQueryParameter($param);

        $values = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values'),
            true,
        );

        return $this->invert($this->storeInSession(
            (\count($values) === 0) || \in_array($value, $values, true),
        ));
    }

    /**
     * Name of the GET parameter this context evaluates.
     *
     * Public so that the cache-identifier and TypoScript "config.linkVars"
     * event listeners can determine the request parameters this extension
     * makes the rendering depend on.
     */
    public function getParameterName(): string
    {
        return trim($this->getConfValue('field_name'));
    }

    /**
     * Whether the GET parameter has to be carried over to all generated links
     * via "config.linkVars".
     *
     * Session-backed contexts do not need it: the match result is remembered
     * in the frontend user session instead of the URL.
     */
    public function addsToLinkVars(): bool
    {
        return !$this->use_session;
    }

    /**
     * Get all query parameters from the current request.
     *
     * @return array<string, mixed> Query parameters
     */
    protected function getQueryParams(): array
    {
        // Try to get from PSR-7 request first (preferred in TYPO3 v12+)
        $request = Container::get()->getRequest();
        if ($request instanceof ServerRequestInterface) {
            return $request->getQueryParams();
        }

        // Fallback to GLOBALS['TYPO3_REQUEST'] if available
        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($globalRequest instanceof ServerRequestInterface) {
            return $globalRequest->getQueryParams();
        }

        // Ultimate fallback to $_GET for backward compatibility
        return $_GET;
    }

    /**
     * Get a query parameter value.
     *
     * @param string $param Parameter name
     *
     * @return mixed Parameter value or null if not set
     */
    protected function getQueryParameter(string $param): mixed
    {
        $queryParams = $this->getQueryParams();
        return $queryParams[$param] ?? null;
    }
}
