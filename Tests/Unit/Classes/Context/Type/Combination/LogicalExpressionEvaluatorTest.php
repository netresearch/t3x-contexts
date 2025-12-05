<?php

declare(strict_types=1);

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

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
}
