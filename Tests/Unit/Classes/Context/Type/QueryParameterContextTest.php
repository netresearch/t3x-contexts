<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\QueryParameterContext;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for QueryParameterContext.
 */
final class QueryParameterContextTest extends UnitTestCase
{
    /**
     * Simulated GET parameters for testing.
     *
     * @var array<string, mixed>
     */
    private array $mockGetParams = [];

    protected function setUp(): void
    {
        parent::setUp();
        error_reporting(error_reporting() & ~\E_NOTICE);
        $this->mockGetParams = [];
    }

    #[Test]
    public function matchParameterMissing(): void
    {
        $context = $this->createContext('affID', '123', []);
        $context->setUseSession(false);

        self::assertFalse($context->match(), 'No parameter means no match');
    }

    #[Test]
    public function matchParameterNoValue(): void
    {
        $context = $this->createContext('affID', '123', ['affID' => '']);
        $context->setUseSession(false);

        self::assertFalse($context->match(), 'Empty value means no match');
    }

    #[Test]
    public function matchParameterCorrectValue(): void
    {
        $context = $this->createContext('affID', '123', ['affID' => '123']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Correct value should match');
    }

    #[Test]
    public function matchParameterCorrectValueOfMany(): void
    {
        $context = $this->createContext('affID', "123\n124\n125\n", ['affID' => '125']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Value in list should match');
    }

    #[Test]
    public function matchParameterWrongValueOfMany(): void
    {
        $context = $this->createContext('affID', "123\n124\n125\n", ['affID' => '124125']);
        $context->setUseSession(false);

        self::assertFalse($context->match(), 'Value not in list should not match');
    }

    #[Test]
    public function matchParameterAnyValue(): void
    {
        $context = $this->createContext('affID', '', ['affID' => 'aslkfj']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Any value should match when no values configured');
    }

    #[Test]
    public function matchParameterAnyValueMissing(): void
    {
        $context = $this->createContext('affID', '', []);
        $context->setUseSession(false);

        self::assertFalse($context->match(), 'Missing parameter should not match');
    }

    #[Test]
    public function matchUnconfiguredNoParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Parameter name missing');

        $context = $this->createContext('', '', []);
        $context->setUseSession(false);

        $context->match();
    }

    #[Test]
    public function matchWithInvertReturnsTrueWhenNoMatch(): void
    {
        $context = $this->createContext('affID', '123', ['affID' => '456']);
        $context->setUseSession(false);
        $context->setInvert(true);

        self::assertTrue($context->match(), 'With invert, non-matching value should return true');
    }

    #[Test]
    public function matchWithInvertReturnsFalseWhenMatch(): void
    {
        $context = $this->createContext('affID', '123', ['affID' => '123']);
        $context->setUseSession(false);
        $context->setInvert(true);

        self::assertFalse($context->match(), 'With invert, matching value should return false');
    }

    #[Test]
    public function matchWithWhitespaceTrimmedParameterName(): void
    {
        $context = $this->createContext('  affID  ', '123', ['affID' => '123']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Parameter name should be trimmed');
    }

    #[Test]
    public function matchWithNumericParameterValue(): void
    {
        $context = $this->createContext('page', '42', ['page' => '42']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Numeric value should match as string');
    }

    #[Test]
    public function matchWithSpecialCharactersInValue(): void
    {
        $context = $this->createContext('filter', 'price>100', ['filter' => 'price>100']);
        $context->setUseSession(false);

        self::assertTrue($context->match(), 'Special characters in value should work');
    }

    #[Test]
    public function matchReturnsFalseWhenParameterMissingAndNoSession(): void
    {
        $context = $this->createContext('missing', 'value', []);
        $context->setUseSession(false);

        self::assertFalse($context->match(), 'Missing parameter without session should not match');
    }

    /**
     * Create a QueryParameterContext with mocked configuration values.
     *
     * @param array<string, mixed> $getParams Simulated GET parameters
     */
    private function createContext(string $fieldName, string $fieldValues, array $getParams = []): QueryParameterContext
    {
        // Update $_GET for the array_key_exists check in match()
        foreach (array_keys($_GET) as $key) {
            unset($_GET[$key]);
        }

        foreach ($getParams as $key => $value) {
            $_GET[$key] = $value;
        }

        return new class ($fieldName, $fieldValues, $getParams) extends QueryParameterContext {
            private readonly string $mockFieldName;

            private readonly string $mockFieldValues;

            /** @var array<string, mixed> */
            private array $mockGetParams;

            /**
             * @param array<string, mixed> $getParams
             */
            public function __construct(string $fieldName, string $fieldValues, array $getParams)
            {
                $this->mockFieldName = $fieldName;
                $this->mockFieldValues = $fieldValues;
                $this->mockGetParams = $getParams;
            }

            protected function getConfValue(
                string $fieldNameArg,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                return match ($fieldNameArg) {
                    'field_name' => $this->mockFieldName,
                    'field_values' => $this->mockFieldValues,
                    default => $default,
                };
            }

            protected function getMatchFromSession(): array
            {
                return [false, null];
            }

            protected function storeInSession(bool $bMatch): bool
            {
                return $bMatch;
            }

            protected function getQueryParameter(string $param): mixed
            {
                return $this->mockGetParams[$param] ?? null;
            }
        };
    }
}
