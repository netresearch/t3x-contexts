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

namespace Netresearch\Contexts\Tests\Unit\Classes\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\CombinationContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use stdClass;

/**
 * Unit tests for CombinationContext.
 *
 * These tests exercise getDependencies() and match() directly,
 * without routing through Container, to verify the class in isolation.
 */
final class CombinationContextTest extends TestBase
{
    /**
     * Provides logical expression scenarios for direct match() testing.
     *
     * @return array<string, array{0: string, 1: bool, 2: bool, 3: bool}>
     *               [label => [expression, ctx1Matched, ctx2Matched, expectedResult]]
     */
    public static function logicalExpressionProvider(): array
    {
        return [
            'AND both true' => ['ctx1 && ctx2', true,  true,  true],
            'AND first false' => ['ctx1 && ctx2', false, true,  false],
            'AND second false' => ['ctx1 && ctx2', true,  false, false],
            'AND both false' => ['ctx1 && ctx2', false, false, false],
            'OR both true' => ['ctx1 || ctx2', true,  true,  true],
            'OR first true' => ['ctx1 || ctx2', true,  false, true],
            'OR second true' => ['ctx1 || ctx2', false, true,  true],
            'OR both false' => ['ctx1 || ctx2', false, false, false],
            'XOR only first true' => ['ctx1 >< ctx2', true,  false, true],
            'XOR only second true' => ['ctx1 >< ctx2', false, true,  true],
            'XOR both true' => ['ctx1 >< ctx2', true,  true,  false],
            'XOR both false' => ['ctx1 >< ctx2', false, false, false],
        ];
    }
    // -----------------------------------------------------------------------
    // getDependencies() tests
    // -----------------------------------------------------------------------

    #[Test]
    public function getDependenciesReturnsEmptyArrayWhenExpressionHasNoVariables(): void
    {
        $combinationContext = $this->createCombinationContext(10, 'combi', '');

        $dependencies = $combinationContext->getDependencies([]);

        self::assertSame([], $dependencies);
    }

    #[Test]
    public function getDependenciesReturnsEmptyArrayWhenNoContextMatchesAlias(): void
    {
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        // No contexts passed - none will match the aliases
        $dependencies = $combinationContext->getDependencies([]);

        self::assertSame([], $dependencies);
    }

    #[Test]
    public function getDependenciesReturnsTrueForEnabledMatchingContext(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        $dependencies = $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        self::assertSame([1 => true], $dependencies);
    }

    #[Test]
    public function getDependenciesReturnsFalseForDisabledMatchingContext(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', true);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        $dependencies = $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        self::assertSame([1 => false], $dependencies);
    }

    #[Test]
    public function getDependenciesCollectsMultipleDistinctContexts(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        $dependencies = $combinationContext->getDependencies([
            1 => $ctx1,
            2 => $ctx2,
            10 => $combinationContext,
        ]);

        self::assertSame([1 => true, 2 => true], $dependencies);
    }

    #[Test]
    public function getDependenciesDeduplicatesRepeatedAliasInExpression(): void
    {
        // "ctx1 && ctx1 || ctx1" references the same alias three times; uid 1 should appear once.
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx1 || ctx1');

        $dependencies = $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        self::assertCount(1, $dependencies);
        self::assertArrayHasKey(1, $dependencies);
    }

    #[Test]
    public function getDependenciesIgnoresAliasesNotFoundInContextList(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        // ctx2 is referenced in the expression but not in the context list
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        $dependencies = $combinationContext->getDependencies([1 => $ctx1, 10 => $combinationContext]);

        // Only ctx1 (uid 1) should be resolved
        self::assertSame([1 => true], $dependencies);
    }

    #[Test]
    public function getDependenciesHandlesMixedEnabledAndDisabledContexts(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false); // enabled
        $ctx2 = $this->createTestContext(2, 'ctx2', true);  // disabled
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        $dependencies = $combinationContext->getDependencies([
            1 => $ctx1,
            2 => $ctx2,
            10 => $combinationContext,
        ]);

        self::assertSame([1 => true, 2 => false], $dependencies);
    }

    #[Test]
    public function getDependenciesAliasComparisonIsCaseInsensitive(): void
    {
        // AbstractContext::getAlias() calls strtolower(), so "CTX1" in the
        // context row resolves to "ctx1". The expression uses "CTX1".
        $ctx = $this->createTestContext(1, 'CTX1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'CTX1');

        $dependencies = $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        self::assertArrayHasKey(1, $dependencies);
    }

    #[Test]
    public function getDependenciesHandlesComplexExpressionWithParentheses(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $ctx3 = $this->createTestContext(3, 'ctx3', true);
        $combinationContext = $this->createCombinationContext(10, 'combi', '(ctx1 && ctx2) || !ctx3');

        $dependencies = $combinationContext->getDependencies([
            1 => $ctx1,
            2 => $ctx2,
            3 => $ctx3,
            10 => $combinationContext,
        ]);

        self::assertSame([1 => true, 2 => true, 3 => false], $dependencies);
    }

    // -----------------------------------------------------------------------
    // match() tests — using crafted dependency stdClass objects
    // as Container passes them (see Container::match() lines 218/225/229)
    // -----------------------------------------------------------------------

    #[Test]
    public function matchReturnsTrueForSingleMatchedDependency(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        // First call getDependencies so the evaluator and tokens are initialised
        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchReturnsFalseForSingleUnmatchedDependency(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = false;

        self::assertFalse($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchReturnsTrueForAndExpressionWhenBothMatch(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsFalseForAndExpressionWhenOneDoesNotMatch(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 && ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        self::assertFalse($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsTrueForOrExpressionWhenOnlyOneMatches(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 || ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = false;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsFalseForOrExpressionWhenBothFail(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 || ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = false;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        self::assertFalse($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsTrueForXorExpressionWhenExactlyOneMatches(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 >< ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsFalseForXorExpressionWhenBothMatch(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 >< ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = true;

        self::assertFalse($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsFalseForXorExpressionWhenBothFail(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 >< ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = false;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        self::assertFalse($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchReturnsTrueForNegatedUnmatchedDependency(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', '!ctx1');

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = false;

        // !false = true
        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchReturnsFalseForNegatedMatchedDependency(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', '!ctx1');

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = true;

        // !true = false
        self::assertFalse($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchInvertsResultWhenInvertFlagIsSet(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        // Expression evaluates to true, but invert=true flips it
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1', true);

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = true;

        self::assertFalse($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchInvertedReturnsTrueWhenExpressionIsFalseAndInvertIsSet(): void
    {
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1', true);

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = false;

        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchUsesUidWhenDependencyContextHasEmptyAlias(): void
    {
        // When context alias is empty, match() still stores uid => matched.
        // The evaluator then looks up the variable by its name in the expression.
        // Because the expression uses the alias name, the result will default to
        // true (unknown variable), so the match should return true.
        $ctx = $this->createTestContext(1, '', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        // ctx1 alias does not exist, so getDependencies returns nothing
        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = false;

        // The evaluator has "ctx1" as a variable, but no value for it; defaults to true
        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchHandlesNestedParenthesesExpression(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $ctx3 = $this->createTestContext(3, 'ctx3', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', '(ctx1 && ctx2) || ctx3');

        $combinationContext->getDependencies([
            1 => $ctx1,
            2 => $ctx2,
            3 => $ctx3,
            10 => $combinationContext,
        ]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        $dep3 = new stdClass();
        $dep3->context = $ctx3;
        $dep3->matched = true;

        // (true && false) || true = false || true = true
        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2, 3 => $dep3]));
    }

    #[Test]
    public function matchHandlesNestedParenthesesExpressionFalse(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $ctx3 = $this->createTestContext(3, 'ctx3', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', '(ctx1 && ctx2) || ctx3');

        $combinationContext->getDependencies([
            1 => $ctx1,
            2 => $ctx2,
            3 => $ctx3,
            10 => $combinationContext,
        ]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        $dep3 = new stdClass();
        $dep3->context = $ctx3;
        $dep3->matched = false;

        // (true && false) || false = false || false = false
        self::assertFalse($combinationContext->match([1 => $dep1, 2 => $dep2, 3 => $dep3]));
    }

    #[Test]
    public function matchHandlesDisabledDependencyTreatedAsMatching(): void
    {
        // When a dependency is disabled, Container passes matched='disabled'.
        // The evaluator treats 'disabled' as true.
        $ctx = $this->createTestContext(1, 'ctx1', true);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = 'disabled';

        // 'disabled' is treated as true in the evaluator
        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    public function matchHandlesWordOperatorsAndOrXor(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        // The tokenizer replaces "and" -> "&&", "or" -> "||", "xor" -> "><"
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 and ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchHandlesWordOrOperator(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 or ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = false;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchHandlesWordXorOperator(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1 xor ctx2');

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = true;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = false;

        self::assertTrue($combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    #[Test]
    public function matchUsesAliasKeyWhenContextHasAlias(): void
    {
        // Context with alias 'ctx1': match() stores both alias and uid
        // in the values array. The evaluator finds the alias key.
        $ctx = $this->createTestContext(1, 'ctx1', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', 'ctx1');

        $combinationContext->getDependencies([1 => $ctx, 10 => $combinationContext]);

        $dep = new stdClass();
        $dep->context = $ctx;
        $dep->matched = true;

        self::assertTrue($combinationContext->match([1 => $dep]));
    }

    #[Test]
    #[DataProvider('logicalExpressionProvider')]
    public function matchEvaluatesLogicalExpressionsCorrectly(
        string $expression,
        bool $ctx1Matched,
        bool $ctx2Matched,
        bool $expectedResult,
    ): void {
        $ctx1 = $this->createTestContext(1, 'ctx1', false);
        $ctx2 = $this->createTestContext(2, 'ctx2', false);
        $combinationContext = $this->createCombinationContext(10, 'combi', $expression);

        $combinationContext->getDependencies([1 => $ctx1, 2 => $ctx2, 10 => $combinationContext]);

        $dep1 = new stdClass();
        $dep1->context = $ctx1;
        $dep1->matched = $ctx1Matched;

        $dep2 = new stdClass();
        $dep2->context = $ctx2;
        $dep2->matched = $ctx2Matched;

        self::assertSame($expectedResult, $combinationContext->match([1 => $dep1, 2 => $dep2]));
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Create an anonymous AbstractContext stub with the given properties.
     */
    private function createTestContext(
        int $uid,
        string $alias,
        bool $disabled,
    ): AbstractContext {
        return new class ($uid, $alias, $disabled) extends AbstractContext {
            public function __construct(int $uid, string $alias, bool $disabled)
            {
                parent::__construct();
                $this->uid = $uid;
                $this->alias = $alias;
                $this->disabled = $disabled;
            }

            public function match(array $arDependencies = []): bool
            {
                return false;
            }
        };
    }

    /**
     * Create a CombinationContext stub that returns the given expression
     * from getConfValue('field_expression').
     */
    private function createCombinationContext(
        int $uid,
        string $alias,
        string $expression,
        bool $invert = false,
    ): CombinationContext {
        return new class ($uid, $alias, $expression, $invert) extends CombinationContext {
            private readonly string $expression;

            public function __construct(int $uid, string $alias, string $expression, bool $invert)
            {
                parent::__construct();
                $this->uid = $uid;
                $this->alias = $alias;
                $this->disabled = false;
                $this->expression = $expression;
                $this->invert = $invert;
            }

            protected function getConfValue(
                string $fieldName,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                if ($fieldName === 'field_expression') {
                    return $this->expression;
                }

                return $default;
            }
        };
    }
}
