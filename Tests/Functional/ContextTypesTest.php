<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional;

use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for different context types matching.
 *
 * Note: Context types (DomainContext, IpContext) use $_SERVER superglobal
 * directly for backwards compatibility with legacy TYPO3 code.
 */
final class ContextTypesTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    /**
     * @var array<string, mixed>
     */
    private array $originalServer = [];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        // Backup original $_SERVER values we'll modify
        $this->originalServer = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        $this->importCSVDataSet(__DIR__ . '/Fixtures/contexts_multiple.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_contexts_settings.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
    }

    protected function tearDown(): void
    {
        Container::reset();

        // Restore original $_SERVER values
        foreach ($this->originalServer as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
            } else {
                $_SERVER[$key] = $value;
            }
        }

        parent::tearDown();
    }

    #[Test]
    public function domainContextMatchesLocalhost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1'; // non-matching IP

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(2);

        self::assertNotNull(
            $context,
            'Domain context should match for localhost',
        );
        self::assertSame('domain', $context->getType());
    }

    #[Test]
    public function domainContextDoesNotMatchUnknownDomain(): void
    {
        $_SERVER['HTTP_HOST'] = 'unknown-domain.test';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = new ServerRequest('http://unknown-domain.test/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find(2),
            'Domain context should not match for unknown-domain.test',
        );
    }

    #[Test]
    public function ipContextMatchesLocalhost(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(3);

        self::assertNotNull(
            $context,
            'IP context should match for 127.0.0.1',
        );
        self::assertSame('ip', $context->getType());
    }

    #[Test]
    public function ipContextDoesNotMatchUnknownIp(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find(3),
            'IP context should not match for 192.168.1.100',
        );
    }

    #[Test]
    public function queryParameterContextMatchesWithParameter(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull(
            $context,
            'QueryParameter context should match with test=1',
        );
        self::assertSame('getparam', $context->getType());
    }

    #[Test]
    public function queryParameterContextDoesNotMatchWithWrongValue(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => 'wrong']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find(1),
            'QueryParameter context should not match with test=wrong',
        );
    }

    #[Test]
    public function disabledContextIsNotMatched(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['x' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find(4),
            'Disabled context should not be matched even with matching parameter',
        );
    }

    #[Test]
    public function invertedContextMatchesWhenConditionFails(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['other' => 'value']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(5);

        self::assertNotNull(
            $context,
            'Inverted context should match when condition is NOT met',
        );
    }

    #[Test]
    public function invertedContextDoesNotMatchWhenConditionSucceeds(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['invert' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find(5),
            'Inverted context should not match when condition IS met',
        );
    }

    #[Test]
    public function multipleContextsCanMatchSimultaneously(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNotNull(Container::get()->find(1), 'QueryParam context should match');
        self::assertNotNull(Container::get()->find(2), 'Domain context should match');
        self::assertNotNull(Container::get()->find(3), 'IP context should match');
    }

    #[Test]
    public function containerFindByAliasWorks(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find('testget');

        self::assertNotNull(
            $context,
            'Should find context by alias',
        );
        self::assertSame(1, $context->getUid());
    }

    #[Test]
    public function containerResetClearsAllContexts(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNotNull(Container::get()->find(1));

        Container::reset();

        // After reset, find returns null (no contexts loaded)
        self::assertNull(Container::get()->find(1));
    }
}
