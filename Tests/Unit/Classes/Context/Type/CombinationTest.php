<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Classes\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\CombinationContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for CombinationContext.
 */
final class CombinationTest extends TestBase
{
    #[Test]
    public function getDependenciesSuccess(): void
    {
        $testContext = $this->createTestContext(123, 'UNITTEST', false);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            '(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST',
        );

        $arContexts = [
            123 => $testContext,
            125 => $combinationContext,
        ];

        $dependencies = $combinationContext->getDependencies($arContexts);

        self::assertArrayHasKey(123, $dependencies);
        self::assertSame([123 => true], $dependencies);
    }

    #[Test]
    public function getDependenciesSuccessWithDisabled(): void
    {
        $testContext = $this->createTestContext(123, 'UNITTEST', true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            '(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST',
        );

        $arContexts = [
            123 => $testContext,
            125 => $combinationContext,
        ];

        $dependencies = $combinationContext->getDependencies($arContexts);

        self::assertArrayHasKey(123, $dependencies);
        self::assertSame([123 => false], $dependencies);
    }

    #[Test]
    public function getDependenciesEmpty(): void
    {
        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            '(context1 && context2 || context3) xor >< context5',
        );

        $arContexts = [
            125 => $combinationContext,
        ];

        $dependencies = $combinationContext->getDependencies($arContexts);

        self::assertEmpty($dependencies);
    }

    #[Test]
    public function matchSuccess(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, true);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST && getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        self::assertCount(3, $matched);
        self::assertArrayHasKey(123, $matched);
        self::assertArrayHasKey(124, $matched);
        self::assertArrayHasKey(125, $matched);
    }

    #[Test]
    public function matchSuccessWithDisabled(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, true);
        $getContext = $this->createTestContext(124, 'getUNITTEST', true, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST && getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // Disabled context (124) should be excluded
        self::assertCount(2, $matched);
        self::assertArrayHasKey(123, $matched);
        self::assertArrayHasKey(125, $matched);
        self::assertArrayNotHasKey(124, $matched);
    }

    #[Test]
    public function matchFailed(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, false);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST && getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // ipContext (123) didn't match, so combination also fails
        self::assertCount(1, $matched);
        self::assertArrayHasKey(124, $matched);
    }

    #[Test]
    public function matchFailedWithDisabled(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, false);
        $getContext = $this->createTestContext(124, 'getUNITTEST', true, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST && getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // Both failed (ip didn't match, get is disabled)
        self::assertEmpty($matched);
    }

    #[Test]
    public function matchSuccessWithOrExpression(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, false);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST || getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // ipContext (123) didn't match, but getContext (124) did, so OR succeeds
        // Only 2 matched: getContext (124) and combinationContext (125)
        self::assertCount(2, $matched);
        self::assertArrayNotHasKey(123, $matched);
        self::assertArrayHasKey(124, $matched);
        self::assertArrayHasKey(125, $matched);
    }

    #[Test]
    public function matchFailedWithOrExpressionBothFalse(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, false);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, false);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST || getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // Both contexts failed, so combination also fails
        self::assertEmpty($matched);
    }

    #[Test]
    public function matchSuccessWithNegatedExpression(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, false);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            '!UNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // ipContext (123) didn't match, but negation makes combination succeed
        self::assertCount(1, $matched);
        self::assertArrayHasKey(125, $matched);
    }

    #[Test]
    public function matchSuccessWithXorExpression(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, true);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, false);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST >< getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // XOR: exactly one is true, so combination succeeds
        self::assertCount(2, $matched);
        self::assertArrayHasKey(123, $matched);
        self::assertArrayHasKey(125, $matched);
    }

    #[Test]
    public function matchFailedWithXorExpressionBothTrue(): void
    {
        $ipContext = $this->createTestContext(123, 'UNITTEST', false, true);
        $getContext = $this->createTestContext(124, 'getUNITTEST', false, true);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST >< getUNITTEST',
        );

        $arContexts = [
            123 => $ipContext,
            124 => $getContext,
            125 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // XOR: both true, so combination fails
        self::assertCount(2, $matched);
        self::assertArrayHasKey(123, $matched);
        self::assertArrayHasKey(124, $matched);
        self::assertArrayNotHasKey(125, $matched);
    }

    #[Test]
    public function getDependenciesWithMultipleReferences(): void
    {
        $testContext = $this->createTestContext(123, 'UNITTEST', false);

        $combinationContext = $this->createCombinationContext(
            125,
            'combiUNITTEST',
            'UNITTEST && UNITTEST || UNITTEST',
        );

        $arContexts = [
            123 => $testContext,
            125 => $combinationContext,
        ];

        $dependencies = $combinationContext->getDependencies($arContexts);

        // Should only contain one entry even though UNITTEST appears multiple times
        self::assertCount(1, $dependencies);
        self::assertArrayHasKey(123, $dependencies);
    }

    #[Test]
    public function matchSuccessWithNestedParentheses(): void
    {
        $ctx1 = $this->createTestContext(1, 'ctx1', false, true);
        $ctx2 = $this->createTestContext(2, 'ctx2', false, false);
        $ctx3 = $this->createTestContext(3, 'ctx3', false, true);

        $combinationContext = $this->createCombinationContext(
            4,
            'combi',
            '(ctx1 && ctx2) || ctx3',
        );

        $arContexts = [
            1 => $ctx1,
            2 => $ctx2,
            3 => $ctx3,
            4 => $combinationContext,
        ];

        $container = new class ($arContexts) extends Container {
            public function __construct(array $contexts)
            {
                parent::__construct($contexts);
            }

            public function invokeMatch(array $arContexts): array
            {
                return $this->match($arContexts);
            }
        };

        $matched = $container->invokeMatch($arContexts);

        // (true && false) || true = false || true = true
        self::assertArrayHasKey(4, $matched);
    }

    /**
     * Create a test context with specified properties.
     */
    private function createTestContext(
        int $uid,
        string $alias,
        bool $disabled = false,
        ?bool $matchResult = null,
    ): AbstractContext {
        return new class ($uid, $alias, $disabled, $matchResult) extends AbstractContext {
            private readonly ?bool $matchResult;

            public function __construct(int $uid, string $alias, bool $disabled, ?bool $matchResult)
            {
                parent::__construct([]);
                $this->uid = $uid;
                $this->alias = $alias;
                $this->disabled = $disabled;
                $this->matchResult = $matchResult;
            }

            public function match(array $arDependencies = []): bool
            {
                return $this->matchResult ?? false;
            }
        };
    }

    /**
     * Create a CombinationContext with mocked expression.
     */
    private function createCombinationContext(int $uid, string $alias, string $expression): CombinationContext
    {
        return new class ($uid, $alias, $expression) extends CombinationContext {
            private readonly string $expression;

            public function __construct(int $uid, string $alias, string $expression)
            {
                parent::__construct([]);
                $this->uid = $uid;
                $this->alias = $alias;
                $this->expression = $expression;
                $this->disabled = false;
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
