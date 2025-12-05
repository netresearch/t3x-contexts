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

            // Empty range
            ['80.76.201.37', '', false],
            ['80.76.201', '', false],

            // Wildcard patterns
            ['80.76.201.37', '80.76.201.*', true],
            ['80.76.201.37', '80.76.*.*', true],
            ['80.76.201.37', '80.76.*', true],
            ['80.76.201.37', '80.76.*.37', true],
            ['80.76.201.37', '80.76.*.40', false],
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
