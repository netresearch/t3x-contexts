<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Service;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Service\PageService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for PageService.
 *
 * PageService provides methods for:
 * - Cache hash modification based on active contexts
 * - Page visibility filtering in menus based on contexts
 */
final class PageServiceTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        // Initialize required TCA structure
        $GLOBALS['TCA']['tx_contexts_contexts'] = [
            'contextTypes' => [],
            'extensionFlatSettings' => [],
            'columns' => [],
        ];
        $GLOBALS['TCA']['pages'] = [
            'ctrl' => [],
            'columns' => [],
        ];
    }

    protected function tearDown(): void
    {
        unset(
            $GLOBALS['TCA']['tx_contexts_contexts'],
            $GLOBALS['TCA']['pages'],
        );
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function implementsSingletonInterface(): void
    {
        $service = new PageService();

        self::assertInstanceOf(SingletonInterface::class, $service);
    }

    #[Test]
    public function createHashBaseModifiesParams(): void
    {
        $params = [
            'hashParameters' => [],
            'createLockHashBase' => false,
        ];

        $service = new PageService();
        $service->createHashBase($params);

        self::assertArrayHasKey('tx_contexts-contexts', $params['hashParameters']);
    }

    #[Test]
    public function createHashBaseAddsEmptyStringWhenNoContexts(): void
    {
        $params = [
            'hashParameters' => [],
        ];

        $service = new PageService();
        $service->createHashBase($params);

        // With no contexts in Container, should be empty string
        self::assertSame('', $params['hashParameters']['tx_contexts-contexts']);
    }

    #[Test]
    public function isPageVisibleInContextReturnsTrueForUnconfiguredPage(): void
    {
        $pageRecord = [
            'uid' => 1,
            'title' => 'Test Page',
        ];

        $service = new PageService();
        $result = $service->isPageVisibleInContext($pageRecord);

        self::assertTrue($result);
    }

    #[Test]
    public function isPageVisibleInContextReturnsTrueWithEmptyFlatColumns(): void
    {
        $GLOBALS['TCA']['pages'] = [
            'ctrl' => [
                'tx_contexts' => [
                    'enableSettings' => ['tx_contexts'],
                    'flatSettings' => [
                        'tx_contexts' => ['tx_contexts_disable', 'tx_contexts_enable'],
                        'tx_contexts_nav' => ['tx_contexts_nav_disable', 'tx_contexts_nav_enable'],
                    ],
                ],
            ],
            'columns' => [],
        ];

        $pageRecord = [
            'uid' => 1,
            'title' => 'Test Page',
            'tx_contexts_disable' => '',
            'tx_contexts_enable' => '',
            'tx_contexts_nav_disable' => '',
            'tx_contexts_nav_enable' => '',
        ];

        $service = new PageService();
        $result = $service->isPageVisibleInContext($pageRecord);

        self::assertTrue($result);
    }

    #[Test]
    public function filterMenuItemsReturnsEmptyArrayForEmptyInput(): void
    {
        $service = new PageService();
        $result = $service->filterMenuItems([]);

        self::assertSame([], $result);
    }

    #[Test]
    public function filterMenuItemsPreservesVisiblePages(): void
    {
        $menuItems = [
            ['uid' => 1, 'title' => 'Page 1'],
            ['uid' => 2, 'title' => 'Page 2'],
        ];

        $service = new PageService();
        $result = $service->filterMenuItems($menuItems);

        self::assertCount(2, $result);
    }

    #[Test]
    public function filterMenuItemsReturnsArrayWithCorrectKeys(): void
    {
        $menuItems = [
            0 => ['uid' => 1, 'title' => 'Page 1'],
            1 => ['uid' => 2, 'title' => 'Page 2'],
            2 => ['uid' => 3, 'title' => 'Page 3'],
        ];

        $service = new PageService();
        $result = $service->filterMenuItems($menuItems);

        // array_filter preserves keys
        self::assertArrayHasKey(0, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
    }
}
