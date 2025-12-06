<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional\Classes\Context;

use Netresearch\Contexts\Context\Container;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Functional tests for Container class focusing on database-dependent methods.
 *
 * Tests coverage for:
 * - loadAvailable() - loads contexts from database
 * - match() - matches contexts and handles dependencies
 * - initAll() - initializes all contexts
 * - initMatching() - initializes matching contexts only
 */
final class ContainerTest extends FunctionalTestCase
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

        // Backup original $_SERVER values we'll modify
        $this->originalServer = [
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? null,
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
        ];

        Container::reset();
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
    public function loadAvailableWithNoContextsReturnsEmptyArray(): void
    {
        // No CSV import - database should be empty for tx_contexts_contexts
        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        self::assertCount(
            0,
            $container,
            'Container should be empty when no contexts in database',
        );
    }

    #[Test]
    public function loadAvailableLoadsContextsFromDatabase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        self::assertCount(
            3,
            $container,
            'Container should load all 3 contexts from database',
        );

        // Verify contexts are properly instantiated
        $context1 = $container->find(100);
        self::assertNotNull($context1, 'Context with UID 100 should exist');
        self::assertSame('Query Param Test', $context1->getTitle());
        self::assertSame('qptest', $context1->getAlias());
        self::assertSame('getparam', $context1->getType());

        $context2 = $container->find(101);
        self::assertNotNull($context2, 'Context with UID 101 should exist');
        self::assertSame('Domain Test', $context2->getTitle());
        self::assertSame('domain', $context2->getType());

        $context3 = $container->find(102);
        self::assertNotNull($context3, 'Context with UID 102 should exist');
        self::assertSame('IP Test', $context3->getTitle());
        self::assertSame('ip', $context3->getType());
    }

    #[Test]
    public function loadAvailableSkipsInvalidContextTypes(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestInvalidType.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        // Should load 2 valid contexts and skip the invalid one
        self::assertCount(
            2,
            $container,
            'Container should skip invalid context type and load only valid ones',
        );

        // Verify invalid context (UID 203) is not present
        self::assertNull(
            $container->find(203),
            'Invalid context type should be skipped',
        );

        // Verify valid contexts are loaded
        self::assertNotNull($container->find(201), 'Valid context 201 should exist');
        self::assertNotNull($container->find(202), 'Valid context 202 should exist');
    }

    #[Test]
    public function initAllLoadsAndStoresAllContexts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'wrong-domain.test';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = new ServerRequest('http://wrong-domain.test/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        // initAll() loads all contexts without matching
        self::assertCount(3, $container, 'initAll should load all contexts');

        // All contexts should be accessible even if they don't match current request
        self::assertNotNull($container->find(100), 'Context 100 should be loaded');
        self::assertNotNull($container->find(101), 'Context 101 should be loaded');
        self::assertNotNull($container->find(102), 'Context 102 should be loaded');
    }

    #[Test]
    public function initMatchingLoadsOnlyMatchingContexts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Should match: query param (100), domain (101), ip (102)
        self::assertGreaterThan(
            0,
            \count($container),
            'Container should have matching contexts',
        );

        // Verify all three contexts match
        self::assertNotNull($container->find(100), 'Query param context should match');
        self::assertNotNull($container->find(101), 'Domain context should match');
        self::assertNotNull($container->find(102), 'IP context should match');
    }

    #[Test]
    public function matchWithNoDependenciesEvaluatesDirectly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1'; // Non-matching IP

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['test' => '1']);

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Query param and domain should match, IP should not
        self::assertNotNull($container->find(100), 'Query param should match');
        self::assertNotNull($container->find(101), 'Domain should match');
        self::assertNull($container->find(102), 'IP should not match');
    }

    #[Test]
    public function matchWithDependenciesResolvesCorrectly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestDependencies.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        // Set query param that matches base context (UID 300)
        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['base' => '1']);

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Base context (300) should match
        self::assertNotNull($container->find(300), 'Base context should match');

        // Combination context (301) depends on base context
        // It should match because base matches and domain matches
        $combinationContext = $container->find(301);
        self::assertNotNull($combinationContext, 'Combination context should match when dependencies match');
        self::assertSame('combination', $combinationContext->getType());
    }

    #[Test]
    public function matchHandlesCircularDependencyPrevention(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestCircular.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Should complete without infinite loop (max 10 iterations)
        // The loop prevention in match() ensures this completes
        self::assertTrue(true, 'Should complete without infinite loop');
    }

    #[Test]
    public function matchSkipsDisabledContexts(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestDisabled.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['disabled' => '1']);

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Disabled context (UID 500) should not be in matched contexts
        self::assertNull(
            $container->find(500),
            'Disabled context should not match even when conditions are met',
        );

        // Enabled context should match
        self::assertNotNull(
            $container->find(501),
            'Enabled context should match',
        );
    }

    #[Test]
    public function matchResolvesMultipleDependencyLevels(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestMultiLevel.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = (new ServerRequest('http://localhost/', 'GET'))
            ->withQueryParams(['level1' => '1']);

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // Level 1 context should match
        $level1 = $container->find(600);
        self::assertNotNull($level1, 'Level 1 context should match');

        // Level 2 depends on Level 1 and domain
        $level2 = $container->find(601);
        self::assertNotNull($level2, 'Level 2 context should match when dependencies resolve');
    }

    #[Test]
    public function matchStopsAfterMaxIterations(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestCircular.csv');

        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '10.0.0.1';

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initMatching();

        // The match() method has a max loop count of 10
        // This ensures it doesn't run forever with circular dependencies
        // If we get here without timeout, the limit works
        self::assertTrue(true, 'Match should stop after max iterations');
    }

    #[Test]
    public function containerRequestCanBeRetrieved(): void
    {
        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);

        self::assertSame(
            $request,
            $container->getRequest(),
            'Should retrieve the same request that was set',
        );
    }

    #[Test]
    public function containerResetClearsState(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container1 = Container::get()->setRequest($request);
        $container1->initAll();

        self::assertCount(3, $container1, 'Container should have 3 contexts');

        Container::reset();

        $container2 = Container::get();
        self::assertCount(0, $container2, 'Container should be empty after reset');
        self::assertNotSame($container1, $container2, 'Reset should create new instance');
    }

    #[Test]
    public function containerIsSingleton(): void
    {
        $container1 = Container::get();
        $container2 = Container::get();

        self::assertSame(
            $container1,
            $container2,
            'Container::get() should return same instance',
        );
    }

    #[Test]
    public function findByUidReturnsCorrectContext(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(100);

        self::assertNotNull($context, 'Should find context by numeric UID');
        self::assertSame(100, $context->getUid());
    }

    #[Test]
    public function findByAliasReturnsCorrectContext(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find('qptest');

        self::assertNotNull($context, 'Should find context by alias');
        self::assertSame(100, $context->getUid());
        self::assertSame('qptest', $context->getAlias());
    }

    #[Test]
    public function findByNonExistentUidReturnsNull(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find(999);

        self::assertNull($context, 'Should return null for non-existent UID');
    }

    #[Test]
    public function findByNonExistentAliasReturnsNull(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/ContainerTestContexts.csv');

        $request = new ServerRequest('http://localhost/', 'GET');

        $container = Container::get()->setRequest($request);
        $container->initAll();

        $context = $container->find('nonexistent');

        self::assertNull($context, 'Should return null for non-existent alias');
    }
}
