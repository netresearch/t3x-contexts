<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for AbstractContext base functionality.
 */
final class AbstractTest extends TestBase
{
    /**
     * @return array<array{0: bool, 1: mixed, 2: array{0: bool, 1: mixed}}>
     */
    public static function sessionProvider(): array
    {
        return [
            'no use, no session' => [false, null, [false, null]],
            'no use, session set true' => [false, true, [false, null]],
            'use session, not set' => [true, null, [false, null]],
            'use session, set true' => [true, true, [true, true]],
            'use session, set false' => [true, false, [true, false]],
            'use session, set string' => [true, 'whatever', [true, true]],
        ];
    }

    #[Test]
    #[DataProvider('sessionProvider')]
    public function getMatchFromSession(bool $useSession, mixed $sessionReturn, array $expected): void
    {
        $stub = new class () extends AbstractContext {
            public mixed $mockSessionReturn = null;

            public function match(array $arDependencies = []): bool
            {
                return true;
            }

            protected function getSession(): mixed
            {
                return $this->mockSessionReturn;
            }
        };

        $stub->setUseSession($useSession);
        $stub->mockSessionReturn = $sessionReturn;

        $result = $this->callProtected($stub, 'getMatchFromSession');
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getRemoteAddressWithProxyGetFirst(): void
    {
        $test = new class () extends AbstractContext {
            public function match(array $arDependencies = []): bool
            {
                return true;
            }
        };

        global $TYPO3_CONF_VARS;
        $TYPO3_CONF_VARS = [
            'SYS' => [
                'reverseProxyIP' => '1.1.1.1',
                'reverseProxyHeaderMultiValue' => 'first',
            ],
        ];

        $_SERVER[AbstractContext::REMOTE_ADDR] = '1.1.1.1';
        $_SERVER[AbstractContext::HTTP_X_FORWARDED_FOR] = '1.2.3.4';

        self::assertSame(
            '1.2.3.4',
            $this->callProtected($test, 'getRemoteAddress'),
        );
    }
}
