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

namespace Netresearch\Contexts\Tests\Unit\ExpressionLanguage\FunctionsProvider;

use Netresearch\Contexts\Api\ContextMatcher;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ExpressionLanguage\FunctionsProvider\ContextFunctionsProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ContextFunctionsProvider.
 *
 * ContextFunctionsProvider implements ExpressionFunctionProviderInterface and
 * exposes the contextMatch() function for use in TYPO3 expression language
 * conditions (TypoScript conditions, TSconfig, etc.).
 *
 * Key responsibilities under test:
 * - Implements the correct interface contract
 * - Returns exactly one ExpressionFunction named "contextMatch"
 * - The evaluator correctly delegates to ContextMatcher::matches()
 * - The compiler callable is intentionally a no-op (only runtime evaluation used)
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

    // ========================================
    // Interface contract
    // ========================================

    #[Test]
    public function implementsExpressionFunctionProviderInterface(): void
    {
        $provider = new ContextFunctionsProvider();

        self::assertInstanceOf(ExpressionFunctionProviderInterface::class, $provider);
    }

    // ========================================
    // getFunctions() — return type and structure
    // ========================================

    #[Test]
    public function getFunctionsReturnsArray(): void
    {
        $provider = new ContextFunctionsProvider();

        self::assertIsArray($provider->getFunctions());
    }

    #[Test]
    public function getFunctionsReturnsExactlyOneFunction(): void
    {
        $provider = new ContextFunctionsProvider();

        self::assertCount(1, $provider->getFunctions());
    }

    #[Test]
    public function getFunctionsReturnsOnlyExpressionFunctionInstances(): void
    {
        $provider = new ContextFunctionsProvider();

        self::assertContainsOnlyInstancesOf(ExpressionFunction::class, $provider->getFunctions());
    }

    #[Test]
    public function getFunctionsProvidesContextMatchFunction(): void
    {
        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        $names = array_map(static fn(ExpressionFunction $f): string => $f->getName(), $functions);

        self::assertContains('contextMatch', $names);
    }

    // ========================================
    // contextMatch — compiler callable (no-op)
    // ========================================

    #[Test]
    public function contextMatchCompilerCallableIsCallable(): void
    {
        $provider = new ContextFunctionsProvider();
        $functions = $provider->getFunctions();

        $contextMatch = $this->findContextMatchFunction($functions);
        self::assertNotNull($contextMatch, 'contextMatch function must be provided');

        $compiler = $contextMatch->getCompiler();
        self::assertIsCallable($compiler);
    }

    #[Test]
    public function contextMatchCompilerCallableReturnsNull(): void
    {
        $provider = new ContextFunctionsProvider();
        $contextMatch = $this->findContextMatchFunction($provider->getFunctions());

        self::assertNotNull($contextMatch);

        $compiler = $contextMatch->getCompiler();
        // The compiler is a no-op static closure that returns void (null)
        $result = $compiler();
        self::assertNull($result);
    }

    // ========================================
    // contextMatch evaluator — delegates to ContextMatcher
    // ========================================

    #[Test]
    public function contextMatchEvaluatorIsCallable(): void
    {
        $provider = new ContextFunctionsProvider();
        $contextMatch = $this->findContextMatchFunction($provider->getFunctions());

        self::assertNotNull($contextMatch);
        self::assertIsCallable($contextMatch->getEvaluator());
    }

    #[Test]
    public function contextMatchEvaluatorReturnsTrueWhenContextIsActive(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('mobile');
        $mockContext->method('getUid')->willReturn(1);

        Container::get()->exchangeArray([1 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        self::assertTrue($evaluator([], 'mobile'));
    }

    #[Test]
    public function contextMatchEvaluatorReturnsFalseWhenContextIsNotActive(): void
    {
        Container::get()->exchangeArray([]);

        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        self::assertFalse($evaluator([], 'nonexistent'));
    }

    #[Test]
    public function contextMatchEvaluatorIsCaseInsensitive(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('desktop');
        $mockContext->method('getUid')->willReturn(2);

        Container::get()->exchangeArray([2 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        self::assertTrue($evaluator([], 'desktop'));
        self::assertTrue($evaluator([], 'DESKTOP'));
        self::assertTrue($evaluator([], 'Desktop'));
    }

    #[Test]
    public function contextMatchEvaluatorReturnsFalseForEmptyContextString(): void
    {
        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        self::assertFalse($evaluator([], ''));
    }

    #[Test]
    public function contextMatchEvaluatorMatchesByAlias(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('my-alias');
        $mockContext->method('getUid')->willReturn(99);

        Container::get()->exchangeArray([99 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        // Alias matches
        self::assertTrue($evaluator([], 'my-alias'));
    }

    #[Test]
    public function contextMatchEvaluatorAlsoMatchesByNumericUid(): void
    {
        $mockContext = $this->createMock(AbstractContext::class);
        $mockContext->method('getAlias')->willReturn('my-alias');
        $mockContext->method('getUid')->willReturn(99);

        // Container stores context at numeric key 99 — find() uses is_numeric check
        Container::get()->exchangeArray([99 => $mockContext]);

        $provider = new ContextFunctionsProvider();
        $evaluator = $this->findContextMatchFunction($provider->getFunctions())->getEvaluator();

        // Container::find() also matches by numeric UID when context is at that key
        self::assertTrue($evaluator([], '99'));
    }

    #[Test]
    public function getFunctionsIsIdempotent(): void
    {
        $provider = new ContextFunctionsProvider();

        $firstCall = $provider->getFunctions();
        $secondCall = $provider->getFunctions();

        self::assertCount(\count($firstCall), $secondCall);
        self::assertSame($firstCall[0]->getName(), $secondCall[0]->getName());
    }

    // ========================================
    // Helpers
    // ========================================

    /**
     * @param ExpressionFunction[] $functions
     */
    private function findContextMatchFunction(array $functions): ?ExpressionFunction
    {
        foreach ($functions as $function) {
            if ($function->getName() === 'contextMatch') {
                return $function;
            }
        }

        return null;
    }
}
