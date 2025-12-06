<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\EventListener\MenuItemFilterEventListener;
use Netresearch\Contexts\Service\PageService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Event\FilterMenuItemsEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for MenuItemFilterEventListener.
 *
 * Note: FilterMenuItemsEvent is final but can be instantiated,
 * allowing us to test the actual listener logic.
 */
final class MenuItemFilterEventListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function constructorAcceptsPageService(): void
    {
        $pageService = $this->createMock(PageService::class);

        $listener = new MenuItemFilterEventListener($pageService);

        self::assertInstanceOf(MenuItemFilterEventListener::class, $listener);
    }

    #[Test]
    public function listenerIsCallable(): void
    {
        $pageService = $this->createMock(PageService::class);

        $listener = new MenuItemFilterEventListener($pageService);

        self::assertIsCallable($listener);
    }

    #[Test]
    public function invokeCallsPageServiceFilterMenuItems(): void
    {
        $menuItems = [
            ['uid' => 1, 'title' => 'Page 1'],
            ['uid' => 2, 'title' => 'Page 2'],
        ];

        $pageService = $this->createMock(PageService::class);
        $pageService->expects(self::once())
            ->method('filterMenuItems')
            ->with($menuItems)
            ->willReturn($menuItems);

        $listener = new MenuItemFilterEventListener($pageService);

        $event = $this->createFilterMenuItemsEvent($menuItems);

        $listener($event);
    }

    #[Test]
    public function invokeUpdatesFilteredMenuItemsInEvent(): void
    {
        $originalItems = [
            ['uid' => 1, 'title' => 'Page 1'],
            ['uid' => 2, 'title' => 'Page 2'],
            ['uid' => 3, 'title' => 'Page 3'],
        ];

        $filteredItems = [
            ['uid' => 1, 'title' => 'Page 1'],
            ['uid' => 3, 'title' => 'Page 3'],
        ];

        $pageService = $this->createMock(PageService::class);
        $pageService->method('filterMenuItems')
            ->willReturn($filteredItems);

        $listener = new MenuItemFilterEventListener($pageService);

        $event = $this->createFilterMenuItemsEvent($originalItems);

        $listener($event);

        self::assertSame($filteredItems, $event->getFilteredMenuItems());
    }

    #[Test]
    public function invokeHandlesEmptyMenuItems(): void
    {
        $emptyItems = [];

        $pageService = $this->createMock(PageService::class);
        $pageService->expects(self::once())
            ->method('filterMenuItems')
            ->with($emptyItems)
            ->willReturn($emptyItems);

        $listener = new MenuItemFilterEventListener($pageService);

        $event = $this->createFilterMenuItemsEvent($emptyItems);

        $listener($event);

        self::assertSame($emptyItems, $event->getFilteredMenuItems());
    }

    #[Test]
    public function invokeCanFilterOutAllItems(): void
    {
        $originalItems = [
            ['uid' => 1, 'title' => 'Restricted Page'],
        ];

        $pageService = $this->createMock(PageService::class);
        $pageService->method('filterMenuItems')
            ->willReturn([]);

        $listener = new MenuItemFilterEventListener($pageService);

        $event = $this->createFilterMenuItemsEvent($originalItems);

        $listener($event);

        self::assertSame([], $event->getFilteredMenuItems());
    }

    #[Test]
    public function listenerIsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(MenuItemFilterEventListener::class);

        self::assertTrue(
            $reflectionClass->isReadOnly(),
            'MenuItemFilterEventListener should be readonly for immutability',
        );
    }

    #[Test]
    public function listenerIsFinal(): void
    {
        $reflectionClass = new ReflectionClass(MenuItemFilterEventListener::class);

        self::assertTrue(
            $reflectionClass->isFinal(),
            'MenuItemFilterEventListener should be final to prevent inheritance issues',
        );
    }

    #[Test]
    public function listenerDependenciesAreInjectedAsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(MenuItemFilterEventListener::class);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            self::assertTrue(
                $property->isReadOnly(),
                \sprintf('Property "%s" should be readonly', $property->getName()),
            );
        }
    }

    /**
     * Helper method to create FilterMenuItemsEvent instances
     */
    private function createFilterMenuItemsEvent(array $filteredMenuItems): FilterMenuItemsEvent
    {
        $site = $this->createMock(Site::class);
        $context = $this->createMock(Context::class);

        return new FilterMenuItemsEvent(
            allMenuItems: $filteredMenuItems,
            filteredMenuItems: $filteredMenuItems,
            menuConfiguration: [],
            itemConfiguration: [],
            bannedMenuItems: [],
            excludedDoktypes: [],
            site: $site,
            context: $context,
            currentPage: [],
        );
    }
}
