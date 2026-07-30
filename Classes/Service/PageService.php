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

use Netresearch\Contexts\Api\Record;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Service for page-related context operations.
 * Provides the active-context signature for page cache differentiation and
 * page/menu filtering.
 *
 * Menu filtering is handled via PSR-14 events
 * (see MenuItemFilterEventListener) rather than hook interfaces.
 *
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class PageService implements SingletonInterface
{
    /**
     * Checks if a page record should be visible in the current context.
     * Used by MenuItemFilterEventListener for PSR-14 event handling.
     *
     * @param array<string, mixed> $pageRecord The page record data
     *
     * @return bool Returns TRUE if the page can be safely included.
     */
    public function isPageVisibleInContext(array $pageRecord): bool
    {
        return Record::isEnabled('pages', $pageRecord)
            && Record::isSettingEnabled('pages', 'tx_contexts_nav', $pageRecord);
    }

    /**
     * Filter menu items based on context visibility.
     * Called from MenuItemFilterEventListener.
     *
     * @param array<int, array<string, mixed>> $menuItems Array of menu item data
     *
     * @return array<int, array<string, mixed>> Filtered menu items
     */
    public function filterMenuItems(array $menuItems): array
    {
        return array_filter(
            $menuItems,
            $this->isPageVisibleInContext(...),
        );
    }

    /**
     * Creates a string that can be used to identify the current
     * context combination.
     *
     * Consumed by PageCacheIdentifierEventListener so that pages rendered with
     * different active contexts get separate page cache entries.
     *
     * @return string Comma separated list of active context UIDs
     */
    public function getHashString(): string
    {
        $keys = array_keys(
            Container::get()->getArrayCopy(),
        );
        sort($keys, \SORT_NUMERIC);
        return implode(',', $keys);
    }
}
