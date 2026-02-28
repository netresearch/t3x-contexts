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
use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

/**
 * Tests for Domain context matching.
 */
final class DomainContextTest extends TestBase
{
    private ?string $originalHttpHost = null;

    protected function setUp(): void
    {
        parent::setUp();
        $httpHost = $_SERVER['HTTP_HOST'] ?? null;
        $this->originalHttpHost = \is_string($httpHost) ? $httpHost : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalHttpHost !== null) {
            $_SERVER['HTTP_HOST'] = $this->originalHttpHost;
        } else {
            unset($_SERVER['HTTP_HOST']);
        }
        parent::tearDown();
    }

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

    #[Test]
    public function matchReturnsTrueForHostWithPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8080';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com:8080');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForHostWithDifferentPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8080';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com:443');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForHostWithPortAgainstDomainWithoutPort(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com:8080';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForWildcardWithDeepSubdomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'deep.sub.www.example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('.example.com');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsTrueWhenInvertedForNonMatchingDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'other.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('example.com');

        $mock->setInvert(true);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForWildcardOnDifferentDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.different.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn('.example.com');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchHandlesMixedExactAndWildcardDomains(): void
    {
        $_SERVER['HTTP_HOST'] = 'www.example.com';

        $mock = $this->createDomainContextMock();
        $mock->method('getConfValue')
            ->with('field_domains')
            ->willReturn("exact.com\n.example.com\nother.org");

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function getCurrentHostFallsBackToGlobalRequestWhenContainerRequestIsNull(): void
    {
        Container::reset();

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getHost')->willReturn('global-host.example.com');

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getUri')->willReturn($mockUri);

        $GLOBALS['TYPO3_REQUEST'] = $mockRequest;

        try {
            $instance = new DomainContext();
            $result = $this->callProtected($instance, 'getCurrentHost');

            self::assertSame('global-host.example.com', $result);
        } finally {
            unset($GLOBALS['TYPO3_REQUEST']);
            Container::reset();
        }
    }

    #[Test]
    public function getCurrentHostFallsBackToServerHttpHost(): void
    {
        Container::reset();
        unset($GLOBALS['TYPO3_REQUEST']);

        $_SERVER['HTTP_HOST'] = 'server-host.example.com';

        try {
            $instance = new DomainContext();
            $result = $this->callProtected($instance, 'getCurrentHost');

            self::assertSame('server-host.example.com', $result);
        } finally {
            Container::reset();
        }
    }

    #[Test]
    public function getCurrentHostReturnsEmptyStringWhenNoSourceAvailable(): void
    {
        Container::reset();
        unset($GLOBALS['TYPO3_REQUEST']);

        $originalHttpHost = $_SERVER['HTTP_HOST'] ?? null;
        unset($_SERVER['HTTP_HOST']);

        try {
            $instance = new DomainContext();
            $result = $this->callProtected($instance, 'getCurrentHost');

            self::assertSame('', $result);
        } finally {
            if ($originalHttpHost !== null) {
                $_SERVER['HTTP_HOST'] = $originalHttpHost;
            }
            Container::reset();
        }
    }

    #[Test]
    public function getCurrentHostUsesContainerRequestFirst(): void
    {
        Container::reset();

        $mockUri = $this->createMock(UriInterface::class);
        $mockUri->method('getHost')->willReturn('container-host.example.com');

        $mockRequest = $this->createMock(ServerRequestInterface::class);
        $mockRequest->method('getUri')->willReturn($mockUri);

        Container::get()->setRequest($mockRequest);

        try {
            $instance = new DomainContext();
            $result = $this->callProtected($instance, 'getCurrentHost');

            self::assertSame('container-host.example.com', $result);
        } finally {
            Container::reset();
        }
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
