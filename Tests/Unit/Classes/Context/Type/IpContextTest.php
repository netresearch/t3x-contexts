<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\IpContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests for IP context matching.
 */
final class IpContextTest extends TestBase
{
    private ?string $originalRemoteAddr = null;

    protected function setUp(): void
    {
        parent::setUp();
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->originalRemoteAddr = is_string($remoteAddr) ? $remoteAddr : null;
    }

    protected function tearDown(): void
    {
        if ($this->originalRemoteAddr !== null) {
            $_SERVER['REMOTE_ADDR'] = $this->originalRemoteAddr;
        } else {
            unset($_SERVER['REMOTE_ADDR']);
        }
        parent::tearDown();
    }
    /**
     * @return array<array{0: string, 1: string, 2: bool}>
     */
    public static function addressProvider(): array
    {
        return [
            // IPv4 CIDR ranges
            ['80.76.201.37', '80.76.201.32/27', true],
            ['80.76.202.37', '80.76.201.32/27', false],

            // IPv6 ranges
            ['FE80:FFFF:0:FFFF:129:144:52:38', 'FE80::/16', true],
            ['FE80:FFFF:0:FFFF:129:144:52:38', 'FE80::/128', false],

            // IPv6 exact match
            ['2001:0db8:85a3:0000:0000:8a2e:0370:7334', '2001:0db8:85a3:0000:0000:8a2e:0370:7334', true],
            ['2001:db8:85a3::8a2e:370:7334', '2001:db8:85a3::8a2e:370:7334', true],

            // IPv6 different address
            ['2001:db8:85a3::8a2e:370:7334', '2001:db8:85a3::8a2e:370:7335', false],

            // IPv6 loopback
            ['::1', '::1', true],

            // Empty range
            ['80.76.201.37', '', false],
            ['80.76.201', '', false],

            // Wildcard patterns
            ['80.76.201.37', '80.76.201.*', true],
            ['80.76.201.37', '80.76.*.*', true],
            ['80.76.201.37', '80.76.*', true],
            ['80.76.201.37', '80.76.*.37', true],
            ['80.76.201.37', '80.76.*.40', false],

            // IPv4 exact match
            ['192.168.1.100', '192.168.1.100', true],
            ['192.168.1.100', '192.168.1.101', false],

            // Multiple comma-separated ranges
            ['10.0.0.5', '192.168.1.0/24,10.0.0.0/8', true],
            ['172.16.0.1', '192.168.1.0/24,10.0.0.0/8', false],
        ];
    }

    #[Test]
    public function matchReturnsTrueForMatchingIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.14';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('192.168.1.14');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseWhenInvertedForMatchingIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.14';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('192.168.1.14');

        $mock->setInvert(true);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsFalseWithNoConfiguration(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.20';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->willReturn('');

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsFalseWithInvalidIp(): void
    {
        $_SERVER['REMOTE_ADDR'] = '';

        $mock = $this->createIpContextMock();
        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    #[DataProvider('addressProvider')]
    public function isIpInRangeReturnsExpectedResult(string $ip, string $range, bool $expectedResult): void
    {
        $instance = new IpContext();

        $result = $this->callProtected(
            $instance,
            'isIpInRange',
            $ip,
            filter_var($ip, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false,
            $range,
        );

        self::assertSame($expectedResult, $result);
    }

    #[Test]
    public function matchReturnsTrueForMultiLineIpConfiguration(): void
    {
        $_SERVER['REMOTE_ADDR'] = '10.0.0.50';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn("192.168.1.0/24\n10.0.0.0/8\n172.16.0.0/12");

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForMultiLineIpConfigurationNotMatching(): void
    {
        $_SERVER['REMOTE_ADDR'] = '8.8.8.8';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn("192.168.1.0/24\n10.0.0.0/8\n172.16.0.0/12");

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForIPv6Address(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2001:db8:85a3::8a2e:370:7334';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('2001:db8::/32');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForIPv6AddressNotInRange(): void
    {
        $_SERVER['REMOTE_ADDR'] = '2001:db8:85a3::8a2e:370:7334';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('fe80::/10');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueForIPv6Loopback(): void
    {
        $_SERVER['REMOTE_ADDR'] = '::1';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('::1');

        $mock->setInvert(false);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchReturnsFalseForMalformedIpAddress(): void
    {
        $_SERVER['REMOTE_ADDR'] = 'not-an-ip-address';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('192.168.1.0/24');

        $mock->setInvert(false);

        self::assertFalse($mock->match());
    }

    #[Test]
    public function matchReturnsTrueWhenInvertedForMalformedIpAddress(): void
    {
        $_SERVER['REMOTE_ADDR'] = 'not-an-ip-address';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('192.168.1.0/24');

        $mock->setInvert(true);

        self::assertTrue($mock->match());
    }

    #[Test]
    public function matchHandlesPartialIpAddress(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1';

        $mock = $this->createIpContextMock();
        $mock->method('getConfValue')
            ->with('field_ip')
            ->willReturn('192.168.1.0/24');

        $mock->setInvert(false);

        // Partial IP is invalid, should return false
        self::assertFalse($mock->match());
    }

    /**
     * Create a mock of IpContext with getConfValue mocked.
     *
     * @return MockObject&IpContext
     */
    protected function createIpContextMock(): MockObject
    {
        return $this->getAccessibleMock(
            IpContext::class,
            ['getConfValue'],
        );
    }
}
