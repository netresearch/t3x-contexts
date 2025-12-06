<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for LogicalExpressionEvaluator.
 */
final class LogicalExpressionEvaluatorTest extends UnitTestCase
{
    /**
     * Provide data for several tests.
     *
     * @return array<int, array{0: string, 1: string, 2: array<string, bool>}>
     *               Array of arguments where:
     *               1st is the expression
     *               2nd is the expected rebuilt expression
     *               3rd are the values
     */
    public static function expressionValueProvider(): array
    {
        return [
            [
                $e = 'context1 || context2',
                $e,
                ['context1' => true, 'context2' => false],
            ],
            [
                'context1 or context2',
                $e,
                ['context1' => true, 'context2' => true],
            ],
            [
                $e = 'context1 && context2',
                $e,
                ['context1' => true, 'context2' => true],
            ],
            [
                'context1 and context2',
                $e,
                ['context1' => true, 'context2' => false],
            ],
            [
                $e = 'context1 >< context2',
                $e,
                ['context1' => true, 'context2' => false],
            ],
            [
                'context1 xor context2',
                $e,
                ['context1' => true, 'context2' => true],
            ],
            [
                'context1 && !(context2 || !!context3)',
                'context1 && !(context2 || context3)',
                ['context1' => true, 'context2' => false, 'context3' => false],
            ],
            [
                'context1 xor (context2 && !context3)',
                'context1 >< (context2 && !context3)',
                ['context1' => true, 'context2' => true, 'context3' => false],
            ],
            [
                $e = 'context1-hyphen && context2',
                $e,
                ['context1-hyphen' => true, 'context2' => true],
            ],
            [
                $e = 'context1_underscore && context2',
                $e,
                ['context1_underscore' => true, 'context2' => true],
            ],
        ];
    }

    /**
     * Helper to evaluate expression using PHP's native evaluation for comparison.
     *
     * @param string              $expression Expression string
     * @param array<string, bool> $values     Variable values
     */
    protected static function getExpressionResult(string $expression, array $values): bool
    {
        // Normalize operators first
        $expression = str_replace('><', 'xor', $expression);
        $expression = (string) preg_replace('/\bor\b/i', '||', $expression);
        $expression = (string) preg_replace('/\band\b/i', '&&', $expression);

        foreach ($values as $key => $value) {
            $expression = str_replace($key, $value ? 'true' : 'false', $expression);
        }

        // Use a safer approach to evaluate the boolean expression
        // by replacing operators and evaluating step by step
        return self::evaluateBooleanExpression($expression);
    }

    /**
     * Safely evaluate a boolean expression string.
     */
    protected static function evaluateBooleanExpression(string $expression): bool
    {
        // Normalize the expression
        $expression = strtolower(trim($expression));

        // Handle parentheses recursively
        while (preg_match('/\(([^()]+)\)/', $expression, $matches) === 1) {
            $inner = self::evaluateBooleanExpression($matches[1]);
            $replacement = $inner ? 'true' : 'false';
            $expression = str_replace($matches[0], $replacement, $expression);
        }

        // Handle NOT operator
        while (preg_match('/!(\s*)(true|false)/', $expression, $matches) === 1) {
            $value = $matches[2] === 'true';
            $replacement = $value === false ? 'true' : 'false';
            $expression = (string) preg_replace('/!\s*(true|false)/', $replacement, $expression, 1);
        }

        // Handle XOR (lowest precedence)
        if (preg_match('/^(true|false)\s*xor\s*(true|false)$/', $expression, $matches) === 1) {
            return ($matches[1] === 'true') xor ($matches[2] === 'true');
        }

        // Handle OR
        if (preg_match('/^(true|false)\s*\|\|\s*(true|false)$/', $expression, $matches) === 1) {
            return ($matches[1] === 'true') || ($matches[2] === 'true');
        }

        // Handle AND
        if (preg_match('/^(true|false)\s*&&\s*(true|false)$/', $expression, $matches) === 1) {
            return ($matches[1] === 'true') && ($matches[2] === 'true');
        }

        // Simple value
        return $expression === 'true';
    }

    #[Test]
    #[DataProvider('expressionValueProvider')]
    public function runWithoutException(string $expression, string $rebuiltExpression, array $values): void
    {
        LogicalExpressionEvaluator::run($expression, $values);
        self::assertSame(
            self::getExpressionResult($expression, $values),
            LogicalExpressionEvaluator::run($expression, $values),
        );
    }

    #[Test]
    #[DataProvider('expressionValueProvider')]
    public function rebuild(string $expression, string $rebuiltExpression, array $values): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $evaluator->parse($evaluator->tokenize($expression));

        self::assertSame(
            // Rebuilt expression is always wrapped within parenthesis
            // because parser always pushes a scope first
            '(' . $rebuiltExpression . ')',
            $evaluator->rebuild(),
            'Rebuild must be revised',
        );
    }

    #[Test]
    public function runWithExceptionUnexpectedEnd(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Unexpected end');

        $strExpression = '(context1 ||';
        $arValues = ['context1' => true];
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    #[Test]
    public function runWithExceptionMissingClosingParentheses(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Missing closing parentheses');

        $strExpression = '(context1 ';
        $arValues = ['context1' => true];
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    #[Test]
    public function runWithExceptionMissingOperator(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Unexpected variable');

        $strExpression = '(context1 context2)';
        $arValues = ['context1' => true];
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    #[Test]
    public function runWithExceptionTwoXor(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage("Can't evaluate more than two items by xor");

        $strExpression = 'context1 xor context2 xor context3';
        $arValues = ['context1' => true, 'context2' => true, 'context3' => true];
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    #[Test]
    public function not(): void
    {
        $strExpression = '!a';
        $arValues = [
            'a' => true,
        ];
        self::assertFalse(
            LogicalExpressionEvaluator::run(
                $strExpression,
                $arValues,
            ),
        );
    }

    #[Test]
    public function andNot(): void
    {
        $strExpression = 'a && !b';
        $arValues = [
            'a' => true,
            'b' => true,
        ];
        self::assertFalse(
            LogicalExpressionEvaluator::run(
                $strExpression,
                $arValues,
            ),
        );
    }

    #[Test]
    public function tokenizeReturnsEmptyTokensForEmptyExpression(): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize('');

        // Empty expression should return only the end token
        self::assertCount(1, $tokens);
        self::assertSame(LogicalExpressionEvaluator::T_END, $tokens[0]);
    }

    #[Test]
    public function tokenizeSingleVariable(): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize('context1');

        self::assertCount(2, $tokens);
        self::assertSame([LogicalExpressionEvaluator::T_VAR, 'context1'], $tokens[0]);
        self::assertSame(LogicalExpressionEvaluator::T_END, $tokens[1]);
    }

    #[Test]
    public function tokenizeVariableAtStartOfExpression(): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize('a && b');

        // Token at position 0 (start) should be properly parsed
        self::assertSame([LogicalExpressionEvaluator::T_VAR, 'a'], $tokens[0]);
        self::assertSame(LogicalExpressionEvaluator::T_AND, $tokens[1]);
        self::assertSame([LogicalExpressionEvaluator::T_VAR, 'b'], $tokens[2]);
    }

    #[Test]
    public function tokenizeHandlesOperatorsCorrectly(): void
    {
        $evaluator = new LogicalExpressionEvaluator();

        // Test && operator
        $andTokens = $evaluator->tokenize('a && b');
        self::assertSame(LogicalExpressionEvaluator::T_AND, $andTokens[1]);

        // Test || operator
        $orTokens = $evaluator->tokenize('a || b');
        self::assertSame(LogicalExpressionEvaluator::T_OR, $orTokens[1]);

        // Test >< operator (XOR)
        $xorTokens = $evaluator->tokenize('a >< b');
        self::assertSame(LogicalExpressionEvaluator::T_XOR, $xorTokens[1]);

        // Test ! operator
        $negTokens = $evaluator->tokenize('!a');
        self::assertSame(LogicalExpressionEvaluator::T_NEGATE, $negTokens[0]);
    }

    #[Test]
    public function tokenizeHandlesParentheses(): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $tokens = $evaluator->tokenize('(a)');

        self::assertSame(LogicalExpressionEvaluator::T_PL, $tokens[0]);
        self::assertSame([LogicalExpressionEvaluator::T_VAR, 'a'], $tokens[1]);
        self::assertSame(LogicalExpressionEvaluator::T_PR, $tokens[2]);
    }

    #[Test]
    public function evaluateDefaultValueWhenVariableNotProvided(): void
    {
        // When a variable is not in values array, it defaults to true
        $result = LogicalExpressionEvaluator::run('missing_var', []);
        self::assertTrue($result);
    }

    #[Test]
    public function evaluateDisabledContextTreatedAsMatching(): void
    {
        // Special case: 'disabled' value is treated as true (matching)
        $result = LogicalExpressionEvaluator::run('context1', ['context1' => 'disabled']);
        self::assertTrue($result);
    }

    #[Test]
    public function evaluateAndReturnsFalseOnFirstFalse(): void
    {
        // AND short-circuits on first false
        $result = LogicalExpressionEvaluator::run('a && b', ['a' => false, 'b' => true]);
        self::assertFalse($result);
    }

    #[Test]
    public function evaluateOrReturnsTrueOnFirstTrue(): void
    {
        // OR short-circuits on first true
        $result = LogicalExpressionEvaluator::run('a || b', ['a' => true, 'b' => false]);
        self::assertTrue($result);
    }

    #[Test]
    public function evaluateXorWithTwoItems(): void
    {
        // XOR: true when exactly one is true
        self::assertTrue(LogicalExpressionEvaluator::run('a >< b', ['a' => true, 'b' => false]));
        self::assertTrue(LogicalExpressionEvaluator::run('a >< b', ['a' => false, 'b' => true]));
        self::assertFalse(LogicalExpressionEvaluator::run('a >< b', ['a' => true, 'b' => true]));
        self::assertFalse(LogicalExpressionEvaluator::run('a >< b', ['a' => false, 'b' => false]));
    }

    #[Test]
    public function evaluateNegatedScope(): void
    {
        // Negated parenthesized expression
        $result = LogicalExpressionEvaluator::run('!(a && b)', ['a' => true, 'b' => true]);
        self::assertFalse($result);

        $result2 = LogicalExpressionEvaluator::run('!(a && b)', ['a' => true, 'b' => false]);
        self::assertTrue($result2);
    }

    #[Test]
    public function rebuildUnshiftedShowsOperators(): void
    {
        $evaluator = new LogicalExpressionEvaluator();
        $evaluator->parse($evaluator->tokenize('a && b'));

        // Unshifted rebuild shows the raw structure
        $unshifted = $evaluator->rebuild(true);
        self::assertStringContainsString('a', $unshifted);
        self::assertStringContainsString('b', $unshifted);
    }

    #[Test]
    public function runWithExceptionUnexpectedOperator(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Unexpected Operator');

        $strExpression = '&& context1';
        LogicalExpressionEvaluator::run($strExpression, []);
    }

    #[Test]
    public function runWithExceptionUnopenedClosingParentheses(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Found not opened closing parentheses');

        $strExpression = 'context1)';
        LogicalExpressionEvaluator::run($strExpression, []);
    }

    #[Test]
    public function runWithExceptionUnexpectedClosingParentheses(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Unexpected )');

        // Expression where ) follows something that isn't a complete expression
        $strExpression = '(a &&)';
        LogicalExpressionEvaluator::run($strExpression, []);
    }

    #[Test]
    public function runWithExceptionNegatedOperator(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage("! can't preceded operators");

        // This creates a situation where ! precedes an operator
        $strExpression = 'a !&& b';
        LogicalExpressionEvaluator::run($strExpression, []);
    }

    #[Test]
    public function runWithExceptionUnexpectedToken(): void
    {
        $this->expectException(LogicalExpressionEvaluatorException::class);
        $this->expectExceptionMessage('Unexpected');

        // Unknown token (special character)
        $strExpression = 'a @ b';
        LogicalExpressionEvaluator::run($strExpression, []);
    }

    #[Test]
    public function precedenceAndBeforeOr(): void
    {
        // AND has higher precedence than OR
        // "a || b && c" should be parsed as "a || (b && c)"
        $result = LogicalExpressionEvaluator::run('a || b && c', [
            'a' => false,
            'b' => true,
            'c' => true,
        ]);
        self::assertTrue($result); // false || (true && true) = true

        $result2 = LogicalExpressionEvaluator::run('a || b && c', [
            'a' => false,
            'b' => true,
            'c' => false,
        ]);
        self::assertFalse($result2); // false || (true && false) = false
    }

    #[Test]
    public function precedenceXorBeforeOr(): void
    {
        // XOR has higher precedence than OR
        // "a || b >< c" should be parsed as "a || (b >< c)"
        $result = LogicalExpressionEvaluator::run('a || b >< c', [
            'a' => false,
            'b' => true,
            'c' => false,
        ]);
        self::assertTrue($result); // false || (true >< false) = true
    }

    #[Test]
    public function nestedParenthesesEvaluation(): void
    {
        $result = LogicalExpressionEvaluator::run('((a && b) || c)', [
            'a' => true,
            'b' => false,
            'c' => true,
        ]);
        self::assertTrue($result); // ((true && false) || true) = true
    }

    #[Test]
    public function complexExpressionWithAllOperators(): void
    {
        $result = LogicalExpressionEvaluator::run('a && (b || !c) && d', [
            'a' => true,
            'b' => false,
            'c' => false,
            'd' => true,
        ]);
        // true && (false || true) && true = true && true && true = true
        self::assertTrue($result);
    }

    #[Test]
    public function doubleNegation(): void
    {
        // !!a should equal a
        self::assertTrue(LogicalExpressionEvaluator::run('!!a', ['a' => true]));
        self::assertFalse(LogicalExpressionEvaluator::run('!!a', ['a' => false]));
    }

    #[Test]
    public function tripleNegation(): void
    {
        // !!!a should equal !a
        self::assertFalse(LogicalExpressionEvaluator::run('!!!a', ['a' => true]));
        self::assertTrue(LogicalExpressionEvaluator::run('!!!a', ['a' => false]));
    }
}
