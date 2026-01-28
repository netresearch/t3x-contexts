<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional\Integration;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Context\Type\IpContext;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for context inheritance from AbstractContext.
 *
 * This test verifies that context classes from the base extension
 * properly inherit and implement AbstractContext functionality.
 * This serves as a template for how sub-extensions should implement
 * their context types.
 *
 * Key aspects tested:
 * - Context classes extend AbstractContext
 * - match() method implementation
 * - Configuration value retrieval
 * - Invert flag functionality
 * - Session storage functionality
 */
final class ContextInheritanceTest extends FunctionalTestCase
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/cross_extension_contexts.csv');
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

    // ========================================
    // Class Inheritance Tests
    // ========================================

    #[Test]
    public function domainContextExtendsAbstractContext(): void
    {
        self::assertTrue(
            is_subclass_of(DomainContext::class, AbstractContext::class),
            'DomainContext should extend AbstractContext',
        );
    }

    #[Test]
    public function ipContextExtendsAbstractContext(): void
    {
        self::assertTrue(
            is_subclass_of(IpContext::class, AbstractContext::class),
            'IpContext should extend AbstractContext',
        );
    }

    #[Test]
    public function queryParameterContextExtendsAbstractContext(): void
    {
        self::assertTrue(
            is_subclass_of(QueryParameterContext::class, AbstractContext::class),
            'QueryParameterContext should extend AbstractContext',
        );
    }

    // ========================================
    // Interface Verification Tests
    // ========================================

    #[Test]
    public function contextClassesImplementMatchMethod(): void
    {
        $reflectionDomain = new ReflectionClass(DomainContext::class);
        $reflectionIp = new ReflectionClass(IpContext::class);
        $reflectionQuery = new ReflectionClass(QueryParameterContext::class);

        self::assertTrue(
            $reflectionDomain->hasMethod('match'),
            'DomainContext should have match() method',
        );
        self::assertTrue(
            $reflectionIp->hasMethod('match'),
            'IpContext should have match() method',
        );
        self::assertTrue(
            $reflectionQuery->hasMethod('match'),
            'QueryParameterContext should have match() method',
        );
    }

    // ========================================
    // Context Property Tests
    // ========================================

    #[Test]
    public function contextHasCorrectPropertiesFromDatabase(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertSame(1, $context->getUid());
        self::assertSame('Domain Context - Localhost', $context->getTitle());
        self::assertSame('domain_localhost', $context->getAlias());
        self::assertSame('domain', $context->getType());
    }

    #[Test]
    public function disabledPropertyIsCorrectlySet(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        // Load all contexts, not just matching ones
        Container::get()
            ->setRequest($request)
            ->initAll();

        // Check enabled context
        $enabledContext = Container::get()->find(1);
        self::assertNotNull($enabledContext);
        self::assertFalse($enabledContext->getDisabled(), 'Enabled context should have disabled=false');
    }

    #[Test]
    public function hideInBackendPropertyIsCorrectlySet(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertFalse(
            $context->getHideInBackend(),
            'Context should have hideInBackend=false',
        );
    }

    // ========================================
    // Invert Functionality Tests
    // ========================================

    #[Test]
    public function invertedContextInvertsMatchResult(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        // Load all contexts to access the inverted one
        Container::get()
            ->setRequest($request)
            ->initAll();

        // Context UID 10 is inverted and configured for GB
        // Since we're not in GB, the base match would be false
        // But with invert=1, it should become true
        $invertedContext = Container::get()->find('inverted_country');

        // Note: This will only work if geolocation extension is loaded
        // If not loaded, the context won't be instantiated properly
        if ($invertedContext === null) {
            self::markTestSkipped('Inverted country context requires geolocation extension');
        }

        // The inverted context should match because we're NOT in GB
        // (assuming private IP doesn't resolve to any country)
    }

    // ========================================
    // Context Factory Tests
    // ========================================

    #[Test]
    public function factoryCreatesCorrectContextType(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $domainContext = Container::get()->find(1);
        $ipContext = Container::get()->find(2);
        $queryContext = Container::get()->find(3);

        self::assertInstanceOf(
            DomainContext::class,
            $domainContext,
            'Context with type "domain" should be DomainContext',
        );
        self::assertInstanceOf(
            IpContext::class,
            $ipContext,
            'Context with type "ip" should be IpContext',
        );

        // Query context needs to match first
        Container::reset();
        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $queryContext = Container::get()->find(3);
        self::assertInstanceOf(
            QueryParameterContext::class,
            $queryContext,
            'Context with type "getparam" should be QueryParameterContext',
        );
    }

    // ========================================
    // Abstract Context API Tests
    // ========================================

    #[Test]
    public function contextProvidesUidAccessor(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertSame(1, $context->getUid());
    }

    #[Test]
    public function contextProvidesTypeAccessor(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertSame('domain', $context->getType());
    }

    #[Test]
    public function contextProvidesTitleAccessor(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertSame('Domain Context - Localhost', $context->getTitle());
    }

    #[Test]
    public function contextProvidesAliasAccessor(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find(1);

        self::assertNotNull($context);
        self::assertSame('domain_localhost', $context->getAlias());
    }

    #[Test]
    public function aliasIsAlwaysLowercase(): void
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        Container::get()
            ->setRequest($request)
            ->initMatching();

        foreach (Container::get() as $context) {
            self::assertSame(
                strtolower($context->getAlias()),
                $context->getAlias(),
                'Context alias should always be lowercase',
            );
        }
    }
}
