<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Service\PageService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\FilterMenuItemsEvent;

/**
 * PSR-14 event listener for filtering menu items based on context.
 * Replaces the legacy AbstractMenuFilterPagesHookInterface hook.
 */
#[AsEventListener(
    identifier: 'contexts/menu-item-filter',
    event: FilterMenuItemsEvent::class,
)]
final readonly class MenuItemFilterEventListener
{
    public function __construct(
        private PageService $pageService,
    ) {
    }

    public function __invoke(FilterMenuItemsEvent $event): void
    {
        $menuItems = $event->getFilteredMenuItems();
        $filteredItems = $this->pageService->filterMenuItems($menuItems);
        $event->setFilteredMenuItems($filteredItems);
    }
}
