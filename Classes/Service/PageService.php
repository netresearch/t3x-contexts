<?php

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
 * Provides methods for cache hash modification and page filtering.
 *
 * Note: In TYPO3 v12+, menu filtering is handled via PSR-14 events
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
     * Modify the cache hash
     *
     * @param array<string, mixed> $params Array of parameters: hashParameters, createLockHashBase
     * @param object|null          $ref    Reference object
     */
    public function createHashBase(array &$params, ?object $ref = null): void
    {
        $params['hashParameters']['tx_contexts-contexts']
            = $this->getHashString();
    }

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
     * Used for cache hash modification.
     *
     * @return string Hash modificator
     */
    protected function getHashString(): string
    {
        $keys = array_keys(
            Container::get()->getArrayCopy(),
        );
        sort($keys, \SORT_NUMERIC);
        return implode(',', $keys);
    }
}
