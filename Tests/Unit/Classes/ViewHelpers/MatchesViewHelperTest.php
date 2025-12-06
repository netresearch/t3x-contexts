<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\ViewHelpers;

use Netresearch\Contexts\Api\ContextMatcher;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ViewHelpers\MatchesViewHelper;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Tests for MatchesViewHelper.
 *
 * The MatchesViewHelper allows Fluid templates to check if a context matches
 * by its alias, returning 1 for match and 0 for no match.
 */
final class MatchesViewHelperTest extends UnitTestCase
{
    private MatchesViewHelper $viewHelper;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset singletons
        ContextMatcher::clearInstance();
        Container::reset();

        $this->viewHelper = new MatchesViewHelper();
        $this->viewHelper->initializeArguments();

        // Set up mock rendering context
        $renderingContext = $this->createMock(RenderingContextInterface::class);
        $this->viewHelper->setRenderingContext($renderingContext);
    }

    protected function tearDown(): void
    {
        ContextMatcher::clearInstance();
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function initializeArgumentsRegistersAliasArgument(): void
    {
        $viewHelper = new MatchesViewHelper();
        $viewHelper->initializeArguments();

        // Use reflection to check registered arguments
        $reflection = new ReflectionClass($viewHelper);
        $property = $reflection->getProperty('argumentDefinitions');
        $arguments = $property->getValue($viewHelper);

        self::assertArrayHasKey('alias', $arguments);
        self::assertTrue($arguments['alias']->isRequired());
    }

    #[Test]
    public function renderReturnsZeroWhenContextDoesNotMatch(): void
    {
        // Empty container means no context matches
        $this->viewHelper->setArguments(['alias' => 'nonexistent']);

        self::assertSame(0, $this->viewHelper->render());
    }

    #[Test]
    public function renderReturnsOneWhenContextMatches(): void
    {
        // Set up a matching context
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('mobile');
        $mockContext->method('getUid')->willReturn(1);

        Container::get()->exchangeArray([1 => $mockContext]);

        $this->viewHelper->setArguments(['alias' => 'mobile']);

        self::assertSame(1, $this->viewHelper->render());
    }

    #[Test]
    public function renderReturnsZeroWhenAliasIsNull(): void
    {
        $this->viewHelper->setArguments(['alias' => null]);

        self::assertSame(0, $this->viewHelper->render());
    }

    #[Test]
    public function renderHandlesEmptyAlias(): void
    {
        $this->viewHelper->setArguments(['alias' => '']);

        // Empty string alias won't match any context
        self::assertSame(0, $this->viewHelper->render());
    }

    #[Test]
    public function renderIsCaseInsensitiveForAlias(): void
    {
        // Container::find() does lowercase comparison
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('testcontext');
        $mockContext->method('getUid')->willReturn(2);

        Container::get()->exchangeArray([2 => $mockContext]);

        $this->viewHelper->setArguments(['alias' => 'TestContext']);

        self::assertSame(1, $this->viewHelper->render());
    }
}
