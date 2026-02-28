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

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\HttpHeaderContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for HTTP Header context matching.
 */
final class HttpHeaderContextTest extends TestBase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalServerVars = [];

    protected function setUp(): void
    {
        parent::setUp();
        /** @var array<string, mixed> $serverVars */
        $serverVars = $_SERVER;
        $this->originalServerVars = $serverVars;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServerVars;
        parent::tearDown();
    }

    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function valueMatchProvider(): array
    {
        return [
            'exact match' => ['mobile', "mobile\ndesktop", true],
            'no match' => ['tablet', "mobile\ndesktop", false],
            'empty config matches non-empty value' => ['something', '', true],
            'empty value with empty config' => ['', '', false],
            'whitespace trimmed' => ['mobile', " mobile \n desktop ", true],
            'substring match' => ['Mozilla/5.0 (iPhone; CPU)', "iPhone\nAndroid", true],
            'case-insensitive match' => ['MOBILE-DEVICE', "mobile\ndesktop", true],
            'no substring match' => ['Windows NT 10.0', "iPhone\nAndroid", false],
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

        $mock = $this->createHttpHeaderContextMock(['completely-unrelated']);
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

        $mock = $this->createHttpHeaderContextMock(['completely-unrelated']);
        $mock->setInvert(true);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForEmptyConfigEvenWithEmptyValue(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = '';

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

    #[Test]
    public function matchIsCaseInsensitiveForHeaderName(): void
    {
        // Header stored with different casing in $_SERVER
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'expected-value';

        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        // Configuration uses lowercase
        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_name', '', 'sDEF', 'lDEF', 'vDEF', 'http_x_custom_header'],
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', 'expected-value'],
            ]);

        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForAnyNonEmptyValueWhenNoValuesConfigured(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = 'any-value-here';

        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        // Empty value list means any non-empty value should match
        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_name', '', 'sDEF', 'lDEF', 'vDEF', 'HTTP_X_CUSTOM_HEADER'],
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', ''],
            ]);

        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForEmptyHeaderValueWithNoValuesConfigured(): void
    {
        $_SERVER['HTTP_X_CUSTOM_HEADER'] = '';

        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_name', '', 'sDEF', 'lDEF', 'vDEF', 'HTTP_X_CUSTOM_HEADER'],
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', ''],
            ]);

        $mock->setInvert(false);
        $mock->setUseSession(false);

        // Empty header value with empty config should return false
        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchHandlesMultipleValuesFromConfig(): void
    {
        $_SERVER['HTTP_X_DEVICE'] = 'tablet';

        $mock = $this->createHttpHeaderContextMock(
            ['mobile', 'tablet', 'desktop'],
            'HTTP_X_DEVICE',
        );
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForValueNotInList(): void
    {
        $_SERVER['HTTP_X_DEVICE'] = 'watch';

        $mock = $this->createHttpHeaderContextMock(
            ['mobile', 'tablet', 'desktop'],
            'HTTP_X_DEVICE',
        );
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueWithInvertForMissingHeader(): void
    {
        unset($_SERVER['HTTP_X_MISSING']);

        $mock = $this->createHttpHeaderContextMock(['any-value'], 'HTTP_X_MISSING');
        $mock->setInvert(true);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForEmptyHeaderName(): void
    {
        $mock = $this->getMockBuilder(HttpHeaderContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_name', '', 'sDEF', 'lDEF', 'vDEF', ''],
                ['field_values', '', 'sDEF', 'lDEF', 'vDEF', 'any-value'],
            ]);

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchSupportsStandardHeaderNameViaPsr7(): void
    {
        Container::reset();

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('hasHeader')
            ->with('User-Agent')
            ->willReturn(true);
        $mockRequest->method('getHeaderLine')
            ->with('User-Agent')
            ->willReturn('Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)');

        Container::get()->setRequest($mockRequest);

        try {
            $mock = $this->getMockBuilder(HttpHeaderContext::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['getConfValue'])
                ->getMock();

            $mock->method('getConfValue')
                ->willReturnMap([
                    ['field_name', '', 'sDEF', 'lDEF', 'vDEF', 'User-Agent'],
                    ['field_values', '', 'sDEF', 'lDEF', 'vDEF', "iPhone\nAndroid"],
                ]);

            $mock->setInvert(false);
            $mock->setUseSession(false);

            self::assertTrue($mock->match());
        } finally {
            Container::reset();
        }
    }

    #[Test]
    public function matchSubstringMatchesUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 12) AppleWebKit/537.36';

        $mock = $this->createHttpHeaderContextMock(
            ['Mobile', 'Android', 'iPhone', 'iPad'],
            'HTTP_USER_AGENT',
        );
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchSubstringDoesNotMatchUnrelatedUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120';

        $mock = $this->createHttpHeaderContextMock(
            ['Mobile', 'Android', 'iPhone', 'iPad'],
            'HTTP_USER_AGENT',
        );
        $mock->setInvert(false);
        $mock->setUseSession(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function getRequestFallsBackToGlobalRequest(): void
    {
        Container::reset();

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $GLOBALS['TYPO3_REQUEST'] = $mockRequest;

        try {
            $instance = new HttpHeaderContext();
            $result = $this->callProtected($instance, 'getRequest');

            self::assertSame($mockRequest, $result);
        } finally {
            unset($GLOBALS['TYPO3_REQUEST']);
            Container::reset();
        }
    }

    #[Test]
    public function getRequestReturnsNullWithNoRequest(): void
    {
        Container::reset();
        unset($GLOBALS['TYPO3_REQUEST']);

        try {
            $instance = new HttpHeaderContext();
            $result = $this->callProtected($instance, 'getRequest');

            self::assertNull($result);
        } finally {
            Container::reset();
        }
    }

    #[Test]
    public function getRequestUsesContainerRequestFirst(): void
    {
        Container::reset();

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        Container::get()->setRequest($mockRequest);

        try {
            $instance = new HttpHeaderContext();
            $result = $this->callProtected($instance, 'getRequest');

            self::assertSame($mockRequest, $result);
        } finally {
            Container::reset();
        }
    }

    #[Test]
    public function findInServerParamsIsCaseInsensitive(): void
    {
        $serverParams = [
            'HTTP_X_CUSTOM' => 'value123',
            'OTHER_KEY' => 'other',
        ];

        $instance = new HttpHeaderContext();
        $result = $this->callProtected($instance, 'findInServerParams', $serverParams, 'http_x_custom');

        self::assertSame('value123', $result);
    }

    #[Test]
    public function findInServerParamsReturnsNullForMissingKey(): void
    {
        $serverParams = [
            'HTTP_X_CUSTOM' => 'value123',
        ];

        $instance = new HttpHeaderContext();
        $result = $this->callProtected($instance, 'findInServerParams', $serverParams, 'HTTP_X_MISSING');

        self::assertNull($result);
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
