<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Api;

use Netresearch\Contexts\Api\ContextMatcher;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ContextMatcher API class.
 *
 * ContextMatcher provides a singleton-based API for checking if contexts match
 * by their alias, with built-in caching of results.
 */
final class ContextMatcherTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset singletons before each test
        ContextMatcher::clearInstance();
        Container::reset();
    }

    protected function tearDown(): void
    {
        // Clean up singletons after each test
        ContextMatcher::clearInstance();
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function getInstanceReturnsSameInstance(): void
    {
        $instance1 = ContextMatcher::getInstance();
        $instance2 = ContextMatcher::getInstance();

        self::assertSame($instance1, $instance2);
    }

    #[Test]
    public function clearInstanceResetsInstance(): void
    {
        $instance1 = ContextMatcher::getInstance();
        ContextMatcher::clearInstance();
        $instance2 = ContextMatcher::getInstance();

        self::assertNotSame($instance1, $instance2);
    }

    #[Test]
    public function matchesReturnsFalseForNonExistentContext(): void
    {
        // Container is empty, so no context will be found
        $matcher = ContextMatcher::getInstance();

        self::assertFalse($matcher->matches('nonexistent'));
    }

    #[Test]
    public function matchesReturnsTrueForExistingContext(): void
    {
        // Set up a context in the container
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('mobile');
        $mockContext->method('getUid')->willReturn(1);

        $container = Container::get();
        $container->exchangeArray([1 => $mockContext]);

        $matcher = ContextMatcher::getInstance();

        self::assertTrue($matcher->matches('mobile'));
    }

    #[Test]
    public function matchesCachesResults(): void
    {
        // Set up a context
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('cached');
        $mockContext->method('getUid')->willReturn(2);

        $container = Container::get();
        $container->exchangeArray([2 => $mockContext]);

        $matcher = ContextMatcher::getInstance();

        // First call
        $result1 = $matcher->matches('cached');

        // Remove context from container to prove caching works
        $container->exchangeArray([]);

        // Second call should return cached result
        $result2 = $matcher->matches('cached');

        self::assertTrue($result1);
        self::assertTrue($result2, 'Result should be cached');
    }

    #[Test]
    public function matchesCachesDifferentResults(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('exists');
        $mockContext->method('getUid')->willReturn(3);

        $container = Container::get();
        $container->exchangeArray([3 => $mockContext]);

        $matcher = ContextMatcher::getInstance();

        // Query both existing and non-existing
        self::assertTrue($matcher->matches('exists'));
        self::assertFalse($matcher->matches('notexists'));

        // Verify they're both cached correctly
        $container->exchangeArray([]);
        self::assertTrue($matcher->matches('exists'), 'True result should be cached');
        self::assertFalse($matcher->matches('notexists'), 'False result should be cached');
    }

    #[Test]
    public function matchesFindsContextByNumericUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('numerictest');
        $mockContext->method('getUid')->willReturn(42);

        $container = Container::get();
        $container->exchangeArray([42 => $mockContext]);

        $matcher = ContextMatcher::getInstance();

        // Container::find() also supports numeric lookup
        self::assertTrue($matcher->matches('42'));
    }
}
