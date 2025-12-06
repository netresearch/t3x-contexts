<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context;

use ArrayObject;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for Container class.
 *
 * Container is a singleton ArrayObject that holds all active contexts.
 * Note: Tests that require database (loadAvailable, initMatching) are in functional tests.
 */
final class ContainerTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
    }

    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function getReturnsSingletonInstance(): void
    {
        $instance1 = Container::get();
        $instance2 = Container::get();

        self::assertSame($instance1, $instance2);
    }

    #[Test]
    public function resetClearsSingletonInstance(): void
    {
        $instance1 = Container::get();
        Container::reset();
        $instance2 = Container::get();

        self::assertNotSame($instance1, $instance2);
    }

    #[Test]
    public function containerIsEmptyByDefault(): void
    {
        $container = Container::get();

        self::assertCount(0, $container);
    }

    #[Test]
    public function containerExtendsArrayObject(): void
    {
        $container = Container::get();

        self::assertInstanceOf(ArrayObject::class, $container);
    }

    #[Test]
    public function setRequestStoresRequest(): void
    {
        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $container = Container::get();

        $result = $container->setRequest($mockRequest);

        self::assertSame($container, $result, 'setRequest should return $this for chaining');
        self::assertSame($mockRequest, $container->getRequest());
    }

    #[Test]
    public function getRequestReturnsNullByDefault(): void
    {
        $container = Container::get();

        self::assertNull($container->getRequest());
    }

    #[Test]
    public function setRequestAcceptsNull(): void
    {
        $container = Container::get();
        $container->setRequest($this->createMock(ServerRequestInterface::class));
        $container->setRequest(null);

        self::assertNull($container->getRequest());
    }

    #[Test]
    public function findReturnsContextByNumericUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(42);
        $mockContext->method('getAlias')->willReturn('testcontext');

        $container = Container::get();
        $container->exchangeArray([42 => $mockContext]);

        self::assertSame($mockContext, $container->find(42));
    }

    #[Test]
    public function findReturnsContextByStringUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(42);
        $mockContext->method('getAlias')->willReturn('testcontext');

        $container = Container::get();
        $container->exchangeArray([42 => $mockContext]);

        self::assertSame($mockContext, $container->find('42'));
    }

    #[Test]
    public function findReturnsContextByAlias(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);
        $mockContext->method('getAlias')->willReturn('mobile');

        $container = Container::get();
        $container->exchangeArray([1 => $mockContext]);

        self::assertSame($mockContext, $container->find('mobile'));
    }

    #[Test]
    public function findIsCaseInsensitiveForAlias(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getUid')->willReturn(1);
        $mockContext->method('getAlias')->willReturn('mobiledevice');

        $container = Container::get();
        $container->exchangeArray([1 => $mockContext]);

        self::assertSame($mockContext, $container->find('MobileDevice'));
    }

    #[Test]
    public function findReturnsNullForNonExistentContext(): void
    {
        $container = Container::get();
        $container->exchangeArray([]);

        self::assertNull($container->find('nonexistent'));
        self::assertNull($container->find(999));
    }

    #[Test]
    public function findPrefersNumericLookupForNumericInput(): void
    {
        // Create two contexts - one with uid=123, one with alias='123'
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(123);
        $context1->method('getAlias')->willReturn('first');

        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(456);
        $context2->method('getAlias')->willReturn('123'); // alias looks numeric

        $container = Container::get();
        $container->exchangeArray([123 => $context1, 456 => $context2]);

        // Should find by array key first for numeric input
        self::assertSame($context1, $container->find(123));
        self::assertSame($context1, $container->find('123'));
    }

    #[Test]
    public function containerIsIterable(): void
    {
        $context1 = $this->createMock(AbstractContext::class);
        $context2 = $this->createMock(AbstractContext::class);

        $container = Container::get();
        $container->exchangeArray([1 => $context1, 2 => $context2]);

        $items = [];
        foreach ($container as $key => $context) {
            $items[$key] = $context;
        }

        self::assertCount(2, $items);
        self::assertSame($context1, $items[1]);
        self::assertSame($context2, $items[2]);
    }

    #[Test]
    public function containerSupportsArrayAccess(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);

        $container = Container::get();
        $container[1] = $mockContext;

        self::assertTrue(isset($container[1]));
        self::assertSame($mockContext, $container[1]);

        unset($container[1]);
        self::assertFalse(isset($container[1]));
    }

    #[Test]
    public function containerPreservesContextsAfterReset(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);

        $container1 = Container::get();
        $container1->exchangeArray([1 => $mockContext]);
        self::assertCount(1, $container1);

        Container::reset();

        // New container should be empty
        $container2 = Container::get();
        self::assertCount(0, $container2);
    }

    // ========================================
    // match() method tests via TestableContainer
    // ========================================

    #[Test]
    public function matchReturnsMatchingContexts(): void
    {
        $matchingContext = $this->createMock(AbstractContext::class);
        $matchingContext->method('getUid')->willReturn(1);
        $matchingContext->method('getDisabled')->willReturn(false);
        $matchingContext->method('getDependencies')->willReturn([]);
        $matchingContext->method('match')->willReturn(true);

        $nonMatchingContext = $this->createMock(AbstractContext::class);
        $nonMatchingContext->method('getUid')->willReturn(2);
        $nonMatchingContext->method('getDisabled')->willReturn(false);
        $nonMatchingContext->method('getDependencies')->willReturn([]);
        $nonMatchingContext->method('match')->willReturn(false);

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $matchingContext, 2 => $nonMatchingContext]);

        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertSame($matchingContext, $result[1]);
    }

    #[Test]
    public function matchSkipsDisabledContexts(): void
    {
        $enabledContext = $this->createMock(AbstractContext::class);
        $enabledContext->method('getUid')->willReturn(1);
        $enabledContext->method('getDisabled')->willReturn(false);
        $enabledContext->method('getDependencies')->willReturn([]);
        $enabledContext->method('match')->willReturn(true);

        $disabledContext = $this->createMock(AbstractContext::class);
        $disabledContext->method('getUid')->willReturn(2);
        $disabledContext->method('getDisabled')->willReturn(true);
        // match() should never be called for disabled contexts

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $enabledContext, 2 => $disabledContext]);

        self::assertCount(1, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayNotHasKey(2, $result);
    }

    #[Test]
    public function matchResolvesDependencyOnMatchedContext(): void
    {
        // Context 1 has no dependencies and matches
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(true);

        // Context 2 depends on context 1
        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        $context2->method('getDependencies')->willReturn([1 => true]);
        $context2->method('match')->willReturn(true);

        $container = new TestableContainer();
        // Order matters: context2 processed after context1 is matched
        $result = $container->exposeMatch([1 => $context1, 2 => $context2]);

        self::assertCount(2, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
    }

    #[Test]
    public function matchResolvesDependencyOnNotMatchedContext(): void
    {
        // Context 1 does not match
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(false);

        // Context 2 depends on context 1 - should still be processed
        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        $context2->method('getDependencies')->willReturn([1 => true]);
        $context2->method('match')->willReturn(true);

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $context1, 2 => $context2]);

        // Context 1 not matched, but context 2 can still match
        self::assertCount(1, $result);
        self::assertArrayNotHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
    }

    #[Test]
    public function matchHandlesDisabledDependency(): void
    {
        // Context 1 will be disabled in dependency (enabled=false)
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(true);

        // Context 2 has a disabled dependency on context 1
        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        // enabled=false means the dependency is disabled (not required)
        $context2->method('getDependencies')->willReturn([1 => false]);
        $context2->method('match')->willReturn(true);

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $context1, 2 => $context2]);

        self::assertCount(2, $result);
    }

    #[Test]
    public function matchDefersProcessingForUnresolvedDependencies(): void
    {
        // Context 2 depends on context 1, but processed first
        // Should be deferred until context 1 is processed
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(true);

        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        $context2->method('getDependencies')->willReturn([1 => true]);
        $context2->method('match')->willReturn(true);

        $container = new TestableContainer();
        // Reverse order - context 2 first in array
        $result = $container->exposeMatch([2 => $context2, 1 => $context1]);

        // Both should be matched after deferred processing
        self::assertCount(2, $result);
        self::assertArrayHasKey(1, $result);
        self::assertArrayHasKey(2, $result);
    }

    #[Test]
    public function matchStopsAfterMaxLoopIterations(): void
    {
        // Create a context with an unresolvable dependency
        $context = $this->createMock(AbstractContext::class);
        $context->method('getUid')->willReturn(1);
        $context->method('getDisabled')->willReturn(false);
        // Depends on non-existent context 999
        $context->method('getDependencies')->willReturn([999 => true]);
        $context->expects(self::never())->method('match');

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $context]);

        // Context never matched due to unresolvable dependency
        self::assertCount(0, $result);
    }

    #[Test]
    public function matchReturnsEmptyArrayForNoMatchingContexts(): void
    {
        $context = $this->createMock(AbstractContext::class);
        $context->method('getUid')->willReturn(1);
        $context->method('getDisabled')->willReturn(false);
        $context->method('getDependencies')->willReturn([]);
        $context->method('match')->willReturn(false);

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $context]);

        self::assertCount(0, $result);
    }

    #[Test]
    public function matchReturnsEmptyArrayForEmptyInput(): void
    {
        $container = new TestableContainer();
        $result = $container->exposeMatch([]);

        self::assertCount(0, $result);
    }

    #[Test]
    public function matchHandlesComplexDependencyChain(): void
    {
        // Context 3 -> Context 2 -> Context 1 (chain of dependencies)
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(true);

        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        $context2->method('getDependencies')->willReturn([1 => true]);
        $context2->method('match')->willReturn(true);

        $context3 = $this->createMock(AbstractContext::class);
        $context3->method('getUid')->willReturn(3);
        $context3->method('getDisabled')->willReturn(false);
        $context3->method('getDependencies')->willReturn([2 => true]);
        $context3->method('match')->willReturn(true);

        $container = new TestableContainer();
        // Worst case order: 3, 2, 1
        $result = $container->exposeMatch([3 => $context3, 2 => $context2, 1 => $context1]);

        self::assertCount(3, $result);
    }

    #[Test]
    public function matchHandlesMultipleDependenciesOnSameContext(): void
    {
        // Context 1 is a dependency for both context 2 and 3
        $context1 = $this->createMock(AbstractContext::class);
        $context1->method('getUid')->willReturn(1);
        $context1->method('getDisabled')->willReturn(false);
        $context1->method('getDependencies')->willReturn([]);
        $context1->method('match')->willReturn(true);

        $context2 = $this->createMock(AbstractContext::class);
        $context2->method('getUid')->willReturn(2);
        $context2->method('getDisabled')->willReturn(false);
        $context2->method('getDependencies')->willReturn([1 => true]);
        $context2->method('match')->willReturn(true);

        $context3 = $this->createMock(AbstractContext::class);
        $context3->method('getUid')->willReturn(3);
        $context3->method('getDisabled')->willReturn(false);
        $context3->method('getDependencies')->willReturn([1 => true]);
        $context3->method('match')->willReturn(true);

        $container = new TestableContainer();
        $result = $container->exposeMatch([
            2 => $context2,
            3 => $context3,
            1 => $context1,
        ]);

        self::assertCount(3, $result);
    }

    #[Test]
    public function matchProcessesAllDisabledContextsWithoutMatch(): void
    {
        $disabled1 = $this->createMock(AbstractContext::class);
        $disabled1->method('getUid')->willReturn(1);
        $disabled1->method('getDisabled')->willReturn(true);

        $disabled2 = $this->createMock(AbstractContext::class);
        $disabled2->method('getUid')->willReturn(2);
        $disabled2->method('getDisabled')->willReturn(true);

        $container = new TestableContainer();
        $result = $container->exposeMatch([1 => $disabled1, 2 => $disabled2]);

        // All disabled, none matched
        self::assertCount(0, $result);
    }
}

/**
 * Testable Container that exposes protected match() method
 */
class TestableContainer extends Container
{
    public function exposeMatch(array $arContexts): array
    {
        return $this->match($arContexts);
    }
}
