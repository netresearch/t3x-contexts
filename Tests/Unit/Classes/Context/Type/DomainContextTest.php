<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for Domain context matching.
 */
final class DomainContextTest extends TestBase
{
    /**
     * @return array<string, array{0: string, 1: string, 2: bool}>
     */
    public static function domainMatchProvider(): array
    {
        return [
            'exact match' => ['example.com', 'example.com', true],
            'exact match different' => ['example.com', 'other.com', false],
            'wildcard subdomain match' => ['www.example.com', '.example.com', true],
            'wildcard subdomain exact' => ['example.com', '.example.com', true],
            'wildcard no match' => ['www.other.com', '.example.com', false],
            'wildcard deep subdomain' => ['sub.www.example.com', '.example.com', true],
            'empty domain' => ['example.com', '', false],
            'empty host' => ['', 'example.com', false],
        ];
    }

    #[Test]
    public function matchReturnsTrueForExactDomainMatch(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForNonMatchingDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'other.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForWildcardSubdomainMatch(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('.example.com');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseWhenInvertedForMatchingDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com');

        $mock->setInvert(true);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForMultipleDomains(): void
    {
        $_SERVER['HTTP_HOST'] = 'second.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn("first.com\nsecond.com\nthird.com");

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseWithNoConfiguration(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->willReturn('');

        self::assertFalse($mock->match());
    }

    #[Test]
    #[DataProvider('domainMatchProvider')]
    public function matchDomainReturnsExpectedResult(string $curHost, string $domain, bool $expectedResult): void
    {
        $instance = new DomainContext();

        $result = $this->callProtected(
            $instance,
            'matchDomain',
            $domain,
            $curHost,
        );

        self::assertSame($expectedResult, $result);
    }

    /**
     * Create a mock of DomainContext with getConfValue mocked.
     *
     * @return MockObject&DomainContext
     */
    protected function createDomainContextMock(): MockObject
    {
        return $this->getAccessibleMock(
            DomainContext::class,
            ['getConfValue'],
        );
    }
}
