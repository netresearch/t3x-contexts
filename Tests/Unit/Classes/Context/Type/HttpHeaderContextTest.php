<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\HttpHeaderContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for HTTP Header context matching.
 */
final class HttpHeaderContextTest extends TestBase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function valueMatchProvider(): array
    {
        return [
            'exact match' => ['mobile', "mobile\ndesktop", true],
            'no match' => ['tablet', "mobile\ndesktop", false],
            'empty config returns false' => ['something', '', false],
            'empty value with empty config' => ['', '', false],
            'whitespace trimmed' => ['mobile', " mobile \n desktop ", true],
        ];
    }

    #[Test]
    public function matchReturnsTrueForExistingHeaderWithMatchingValue(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'expected-value';

        $mock = $this->createHttpHeaderContextMock(['expected-value']);
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForExistingHeaderWithNonMatchingValue(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'different-value';

        $mock = $this->createHttpHeaderContextMock(['expected-value']);
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForNonExistingHeader(): void
    {
        // Ensure the header doesn't exist
        unset($_SERVER['HTTP_X_CUSTOM_HEADER']);

        $mock = $this->createHttpHeaderContextMock(['any-value'], 'HTTP_X_NONEXISTENT');
        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueWhenInvertedForNonMatchingValue(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'different-value';

        $mock = $this->createHttpHeaderContextMock(['expected-value']);
        $mock->setInvert(true);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForEmptyConfigEvenWithValue(): void
    {
        // Empty config (no allowed values) means NO values are allowed
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'any-value';

        $mock = $this->createHttpHeaderContextMock([], 'HTTP_X_CUSTOM_HEADER', '');
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    #[DataProvider('valueMatchProvider')]
    public function matchValuesReturnsExpectedResult(string $value, string $configuredValues, bool $expectedResult): void
    {
        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', $configuredValues],
            ]);

        $result = $this->callProtected($mock, 'matchValues', $value);

        self::assertSame($expectedResult, $result);
    }

    /**
     * Create a mock of HttpHeaderContext with getConfValue mocked.
     *
     * @param list<string> $allowedValues
     *
     * @return MockObject&HttpHeaderContext
     */
    protected function createHttpHeaderContextMock(
        array $allowedValues,
        string $headerName = 'HTTP_X_CUSTOM_HEADER',
        ?string $valuesConfig = null,
    ): MockObject {
        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        $valuesString = $valuesConfig ?? implode("\n", $allowedValues);

        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_name', '', 'sDEF', 'lDEF', 'vDEF', $headerName],
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', $valuesString],
            ]);

        return $mock;
    }
}
