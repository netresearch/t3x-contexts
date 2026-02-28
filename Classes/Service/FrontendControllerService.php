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

use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class FrontendControllerService
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class FrontendControllerService implements SingletonInterface
{
    protected static bool $hooksRegistered = false;

    /**
     * Parameters that modify the page hash.
     * Key is the parameter name, value its value.
     *
     */
    protected static array $params = [];

    /**
     * GET parameters that are carried across all URLs.
     * Key is the parameter name, value is ignored
     *
     */
    protected static array $linkVarParams = [];

    /**
     * Register a param, it's value and hook into configArrayPostProc and
     * createHashBase/cHashParamsHook
     *
     * @param mixed  $value
     *
     */
    public static function registerQueryParameter(string $param, $value, bool $addToLinkVars): void
    {
        self::$params[$param] = $value;

        if ($addToLinkVars) {
            self::$linkVarParams[$param] = true;
        }

        if (self::$hooksRegistered) {
            return;
        }

        self::$hooksRegistered = true;

        $conf = &$GLOBALS['TYPO3_CONF_VARS'];

        // Hook for postProcessing the configuration array
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][self::class]
            = self::class . '->configArrayPostProc';

        // Hook to influence the page hash calculation
        $conf['SC_OPTIONS']['tslib/class.tslib_fe.php']['createHashBase'][self::class]
            = self::class . '->createHashBase';
    }

    /**
     * Check if page is accessible based on context settings in rootline.
     *
     * The current page (first element) is always checked for its own context
     * restrictions. Parent pages are only checked if they have extendToSubpages
     * enabled, which propagates their restrictions down the page tree.
     *
     * @param array<int, array<string, mixed>> $rootLine The page rootline
     *                                                    (current page first, root page last)
     *
     * @return bool FALSE if context restrictions deny access
     */
    public function checkEnableFieldsForRootLine(array $rootLine): bool
    {
        if ($rootLine === []) {
            return true;
        }

        $contexts = Container::get();

        // Always check the current page (index 0) for its own context restrictions.
        // Without this, pages with tx_contexts_enable would be accessible directly
        // even when the required context is not active.
        $currentPage = reset($rootLine);

        if (\is_array($currentPage) && !$this->isPageAccessible($currentPage, $contexts)) {
            return false;
        }

        // Check parent pages (skip index 0 = current page).
        // Parent pages only apply restrictions if extendToSubpages is enabled.
        $parentPages = \array_slice($rootLine, 1);
        $findExtendSubPage = false;

        foreach (array_reverse($parentPages) as $page) {
            if (($page['extendToSubpages'] ?? 0) === 0 && !$findExtendSubPage) {
                continue;
            }

            $findExtendSubPage = true;

            if (!$this->isPageAccessible($page, $contexts)) {
                return false;
            }
        }

        return true;
    }

    /**
     * methods related to the QueryParameterContext
     */

    /**
     * Add a parameter to cHash so that caches are specific to the current context combination.
     *
     * @param array                        $params Array of parameters
     *
     */
    public function createHashBase(array &$params, TypoScriptFrontendController $tsfe): void
    {
        ksort(self::$params);

        $params['hashParameters'][strtolower(self::class)] = serialize(self::$params);
    }

    /**
     * Add current param names to config.linkVars.
     *
     * @param array                        $params Array of parameters
     *
     */
    public function configArrayPostProc(array &$params, TypoScriptFrontendController $tsfe): void
    {
        $linkVars = (string) ($params['config']['linkVars'] ?? '') . ',' . implode(',', array_keys(self::$linkVarParams));
        $params['config']['linkVars'] = trim($linkVars, ',');
    }

    /**
     * Check if a single page is accessible based on its context enable/disable settings.
     *
     * @param array<string, mixed> $page     The page record
     * @param Container            $contexts The active contexts container
     *
     * @return bool FALSE if context restrictions deny access
     */
    protected function isPageAccessible(array $page, Container $contexts): bool
    {
        $enable = array_filter(explode(',', (string) ($page['tx_contexts_enable'] ?? '')));

        foreach ($enable as $contextId) {
            if (!isset($contexts[$contextId])) {
                return false;
            }
        }

        $disable = array_filter(explode(',', (string) ($page['tx_contexts_disable'] ?? '')));

        foreach ($disable as $contextId) {
            if (isset($contexts[$contextId])) {
                return false;
            }
        }

        return true;
    }
}
