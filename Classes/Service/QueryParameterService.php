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

namespace Netresearch\Contexts\Service;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Provides the GET parameters the configured "getparam" contexts depend on.
 *
 * Two consumers exist, both of them PSR-14 event listeners:
 *
 * - PageCacheIdentifierEventListener adds the current values of these
 *   parameters to the page cache identifier, so that a page rendered with a
 *   context-switching parameter never overwrites the cache entry of the same
 *   page rendered without it.
 * - TypoScriptConfigEventListener adds the parameter names to
 *   "config.linkVars", so that the active channel survives following a link.
 *
 * The parameter names are derived from the *available* contexts, not from the
 * matched ones: "config.linkVars" is written into the cached TypoScript config,
 * so it must not depend on the current request.
 *
 * Because that TypoScript cache is keyed by the TypoScript sources and not by
 * the context records, DataHandlerService flushes the "pages" cache group
 * whenever a context record is written or removed. Otherwise a new "getparam"
 * context would never reach "config.linkVars".
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class QueryParameterService implements SingletonInterface
{
    /**
     * Names of all GET parameters used by a query parameter context.
     *
     * @var list<string>|null
     */
    private ?array $parameterNames = null;

    /**
     * Names of the GET parameters that have to be carried over to all links.
     *
     * @var list<string>|null
     */
    private ?array $linkVarNames = null;

    /**
     * All GET parameter names any query parameter context evaluates.
     *
     * @return list<string>
     */
    public function getParameterNames(): array
    {
        $this->loadParameterNames();

        return $this->parameterNames ?? [];
    }

    /**
     * The GET parameter names to append to "config.linkVars".
     *
     * @return list<string>
     */
    public function getLinkVarNames(): array
    {
        $this->loadParameterNames();

        return $this->linkVarNames ?? [];
    }

    /**
     * Current values of all context-relevant GET parameters.
     *
     * Only parameters actually present in the request are returned. The result
     * is sorted by name so that the same request always yields the same array.
     *
     * @return array<string, mixed> Key is the parameter name, value its value
     */
    public function getParameterValues(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();
        $values = [];

        foreach ($this->getParameterNames() as $name) {
            if (\array_key_exists($name, $queryParams)) {
                $values[$name] = $queryParams[$name];
            }
        }

        ksort($values);

        return $values;
    }

    /**
     * Reset the internal cache.
     *
     * Called by DataHandlerService::flushContextCaches() whenever a context
     * record was written in this request, and by the tests.
     */
    public function reset(): void
    {
        $this->parameterNames = null;
        $this->linkVarNames = null;
    }

    /**
     * All configured contexts, matched or not.
     *
     * @return array<int|string, AbstractContext>
     *
     * @codeCoverageIgnore Requires database via Container::loadAvailable()
     */
    protected function getAvailableContexts(): array
    {
        return Container::get()->loadAvailable();
    }

    /**
     * Collect the parameter names from all available query parameter contexts.
     */
    private function loadParameterNames(): void
    {
        if ($this->parameterNames !== null) {
            return;
        }

        $parameterNames = [];
        $linkVarNames = [];

        foreach ($this->getAvailableContexts() as $context) {
            if (!$context instanceof QueryParameterContext) {
                continue;
            }

            if ($context->getDisabled()) {
                continue;
            }

            $name = $context->getParameterName();

            if ($name === '') {
                continue;
            }

            $parameterNames[$name] = true;

            if ($context->addsToLinkVars()) {
                $linkVarNames[$name] = true;
            }
        }

        $this->parameterNames = array_keys($parameterNames);
        $this->linkVarNames = array_keys($linkVarNames);

        sort($this->parameterNames);
        sort($this->linkVarNames);
    }
}
