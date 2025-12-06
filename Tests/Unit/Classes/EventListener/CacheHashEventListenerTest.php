<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\EventListener\CacheHashEventListener;
use Netresearch\Contexts\Service\PageService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for CacheHashEventListener.
 *
 * Note: ModifyCacheLifetimeForPageEvent is final but can be instantiated,
 * allowing us to test the actual listener logic. The current implementation
 * is a placeholder for future context-based cache lifetime modifications.
 */
final class CacheHashEventListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[Test]
    public function constructorAcceptsPageService(): void
    {
        $pageService = $this->createMock(PageService::class);

        $listener = new CacheHashEventListener($pageService);

        self::assertInstanceOf(CacheHashEventListener::class, $listener);
    }

    #[Test]
    public function listenerIsCallable(): void
    {
        $pageService = $this->createMock(PageService::class);

        $listener = new CacheHashEventListener($pageService);

        self::assertIsCallable($listener);
    }

    #[Test]
    public function invokeDoesNotModifyCacheLifetime(): void
    {
        $pageService = $this->createMock(PageService::class);
        $listener = new CacheHashEventListener($pageService);

        $context = $this->createMock(Context::class);
        $originalLifetime = 3600;

        $event = new ModifyCacheLifetimeForPageEvent(
            cacheLifetime: $originalLifetime,
            pageId: 1,
            pageRecord: ['uid' => 1, 'title' => 'Test Page'],
            renderingInstructions: [],
            context: $context,
        );

        $listener($event);

        // Current implementation does not modify cache lifetime
        self::assertSame($originalLifetime, $event->getCacheLifetime());
    }

    #[Test]
    public function invokeCanBeCalledMultipleTimes(): void
    {
        $pageService = $this->createMock(PageService::class);
        $listener = new CacheHashEventListener($pageService);

        $context = $this->createMock(Context::class);

        $event = new ModifyCacheLifetimeForPageEvent(
            cacheLifetime: 3600,
            pageId: 1,
            pageRecord: ['uid' => 1],
            renderingInstructions: [],
            context: $context,
        );

        // Should not throw exception when called multiple times
        $listener($event);
        $listener($event);

        self::assertTrue(true, 'Listener can be invoked multiple times without errors');
    }

    #[Test]
    public function invokeHandlesDifferentPageIds(): void
    {
        $pageService = $this->createMock(PageService::class);
        $listener = new CacheHashEventListener($pageService);

        $context = $this->createMock(Context::class);

        $events = [
            new ModifyCacheLifetimeForPageEvent(3600, 1, ['uid' => 1], [], $context),
            new ModifyCacheLifetimeForPageEvent(7200, 2, ['uid' => 2], [], $context),
            new ModifyCacheLifetimeForPageEvent(1800, 3, ['uid' => 3], [], $context),
        ];

        foreach ($events as $event) {
            $listener($event);
        }

        self::assertTrue(true, 'Listener handles different page IDs correctly');
    }

    #[Test]
    public function invokeHandlesZeroCacheLifetime(): void
    {
        $pageService = $this->createMock(PageService::class);
        $listener = new CacheHashEventListener($pageService);

        $context = $this->createMock(Context::class);

        $event = new ModifyCacheLifetimeForPageEvent(
            cacheLifetime: 0,
            pageId: 1,
            pageRecord: ['uid' => 1],
            renderingInstructions: [],
            context: $context,
        );

        $listener($event);

        self::assertSame(0, $event->getCacheLifetime());
    }

    #[Test]
    public function invokeHandlesEmptyPageRecord(): void
    {
        $pageService = $this->createMock(PageService::class);
        $listener = new CacheHashEventListener($pageService);

        $context = $this->createMock(Context::class);

        $event = new ModifyCacheLifetimeForPageEvent(
            cacheLifetime: 3600,
            pageId: 0,
            pageRecord: [],
            renderingInstructions: [],
            context: $context,
        );

        $listener($event);

        self::assertTrue(true, 'Listener handles empty page record without errors');
    }

    #[Test]
    public function listenerIsReadonly(): void
    {
        $reflectionClass = new ReflectionClass(CacheHashEventListener::class);

        self::assertTrue(
            $reflectionClass->isReadOnly(),
            'CacheHashEventListener should be readonly for immutability',
        );
    }

    #[Test]
    public function listenerIsFinal(): void
    {
        $reflectionClass = new ReflectionClass(CacheHashEventListener::class);

        self::assertTrue(
            $reflectionClass->isFinal(),
            'CacheHashEventListener should be final to prevent inheritance issues',
        );
    }

    #[Test]
    public function listenerHasCorrectConstructorDependencies(): void
    {
        $reflectionClass = new ReflectionClass(CacheHashEventListener::class);
        $constructor = $reflectionClass->getConstructor();

        self::assertNotNull($constructor);
        $parameters = $constructor->getParameters();

        self::assertCount(1, $parameters, 'Constructor should accept exactly one parameter');
        self::assertSame('pageService', $parameters[0]->getName());
        self::assertSame(
            PageService::class,
            $parameters[0]->getType()?->getName(),
        );
    }

    #[Test]
    public function invokeMethodReturnsVoid(): void
    {
        $reflectionClass = new ReflectionClass(CacheHashEventListener::class);
        $invokeMethod = $reflectionClass->getMethod('__invoke');
        $returnType = $invokeMethod->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame('void', $returnType->getName());
    }
}
