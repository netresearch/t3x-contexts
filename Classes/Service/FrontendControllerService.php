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

/**
 * Class FrontendControllerService
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class FrontendControllerService implements SingletonInterface
{
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
