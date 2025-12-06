<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\ExpressionLanguage;

use Netresearch\Contexts\Api\ContextMatcher;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ExpressionLanguage\FunctionsProvider\ContextFunctionsProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ContextFunctionsProvider.
 *
 * This provider adds the contextMatch() function to TYPO3's TypoScript conditions,
 * allowing conditions like [contextMatch("mobile")] in TypoScript.
 */
final class ContextFunctionsProviderTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
        ContextMatcher::clearInstance();
    }

    protected function tearDown(): void
    {
        Container::reset();
        ContextMatcher::clearInstance();
        parent::tearDown();
    }

    #[Test]
    public function getFunctionsProvidesContextMatchFunction(): void
    {
        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        self::assertNotEmpty($functions);
        self::assertContainsOnlyInstancesOf(ExpressionFunction::class, $functions);

        // Find the contextMatch function
        $contextMatchFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                $contextMatchFunction = $function;
                break;
            }
        }

        self::assertNotNull($contextMatchFunction, 'contextMatch() function should be provided');
    }

    #[Test]
    public function contextMatchFunctionReturnsTrueForMatchingContext(): void
    {
        // Set up a matching context
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('mobile');
        $mockContext->method('getUid')->willReturn(1);

        Container::get()->exchangeArray([1 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        // Get the evaluator from the contextMatch function
        $contextMatchFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                $contextMatchFunction = $function;
                break;
            }
        }

        self::assertNotNull($contextMatchFunction, 'contextMatch function should exist');

        // The evaluator is the second element in the ExpressionFunction
        $evaluator = $contextMatchFunction->getEvaluator();
        $result = $evaluator([], 'mobile');

        self::assertTrue($result);
    }

    #[Test]
    public function contextMatchFunctionReturnsFalseForNonMatchingContext(): void
    {
        // Empty container - no contexts match
        Container::get()->exchangeArray([]);

        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        $contextMatchFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                $contextMatchFunction = $function;
                break;
            }
        }

        self::assertNotNull($contextMatchFunction, 'contextMatch function should exist');

        $evaluator = $contextMatchFunction->getEvaluator();
        $result = $evaluator([], 'nonexistent');

        self::assertFalse($result);
    }

    #[Test]
    public function contextMatchFunctionIsCaseInsensitive(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('desktop');
        $mockContext->method('getUid')->willReturn(2);

        Container::get()->exchangeArray([2 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        $contextMatchFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                $contextMatchFunction = $function;
                break;
            }
        }

        self::assertNotNull($contextMatchFunction, 'contextMatch function should exist');

        $evaluator = $contextMatchFunction->getEvaluator();

        self::assertTrue($evaluator([], 'Desktop'));
        self::assertTrue($evaluator([], 'DESKTOP'));
        self::assertTrue($evaluator([], 'desktop'));
    }

    #[Test]
    public function contextMatchFunctionHandlesEmptyString(): void
    {
        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        $contextMatchFunction = null;
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                $contextMatchFunction = $function;
                break;
            }
        }

        self::assertNotNull($contextMatchFunction, 'contextMatch function should exist');

        $evaluator = $contextMatchFunction->getEvaluator();
        $result = $evaluator([], '');

        self::assertFalse($result);
    }
}
