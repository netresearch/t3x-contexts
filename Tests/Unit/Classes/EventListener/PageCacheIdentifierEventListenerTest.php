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

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Netresearch\Contexts\EventListener\PageCacheIdentifierEventListener;
use Netresearch\Contexts\Service\PageService;
use Netresearch\Contexts\Service\QueryParameterService;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for PageCacheIdentifierEventListener.
 *
 * The listener replaces the SC_OPTIONS 'createHashBase' hook removed in TYPO3
 * v13.0 (#102932). Its whole job is that two renderings of the same page with
 * different active contexts do not share a page cache entry, so the tests
 * assert exactly that: the identifier parameters differ.
 */
final class PageCacheIdentifierEventListenerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();
    }

    protected function tearDown(): void
    {
        Container::reset();

        parent::tearDown();
    }

    #[Test]
    public function addsTheActiveContextsToTheCacheIdentifier(): void
    {
        Container::get()->offsetSet('42', $this->createMock(AbstractContext::class));

        $event = $this->dispatch($this->createRequest());

        $parameters = $event->getPageCacheIdentifierParameters();

        self::assertArrayHasKey('tx_contexts', $parameters);
        self::assertSame('42', $parameters['tx_contexts']['contexts']);
    }

    #[Test]
    public function keepsExistingCacheIdentifierParameters(): void
    {
        $event = $this->dispatch($this->createRequest(), ['pageId' => 1]);

        self::assertSame(1, $event->getPageCacheIdentifierParameters()['pageId']);
    }

    #[Test]
    public function cacheIdentifierDiffersBetweenActiveContextCombinations(): void
    {
        $withoutContext = $this->dispatch($this->createRequest())
            ->getPageCacheIdentifierParameters();

        Container::get()->offsetSet('42', $this->createMock(AbstractContext::class));

        $withContext = $this->dispatch($this->createRequest())
            ->getPageCacheIdentifierParameters();

        self::assertNotSame(
            $withoutContext,
            $withContext,
            'A page rendered with an active context must not reuse the cache entry of the same page rendered without it',
        );
        self::assertNotSame(
            $this->hash($withoutContext),
            $this->hash($withContext),
            'The resulting cache identifier hash must differ',
        );
    }

    #[Test]
    public function cacheIdentifierDiffersBetweenValuesOfAContextParameter(): void
    {
        $mobile = $this->dispatch(
            $this->createRequest(['channel' => 'mobile']),
        )->getPageCacheIdentifierParameters();

        $desktop = $this->dispatch(
            $this->createRequest(['channel' => 'desktop']),
        )->getPageCacheIdentifierParameters();

        self::assertNotSame(
            $this->hash($mobile),
            $this->hash($desktop),
            'Different values of a context GET parameter must produce different cache entries',
        );
    }

    #[Test]
    public function cacheIdentifierIsStableForIdenticalRequests(): void
    {
        Container::get()->offsetSet('42', $this->createMock(AbstractContext::class));

        $first = $this->dispatch($this->createRequest(['channel' => 'mobile']))
            ->getPageCacheIdentifierParameters();
        $second = $this->dispatch($this->createRequest(['channel' => 'mobile']))
            ->getPageCacheIdentifierParameters();

        self::assertSame(
            $this->hash($first),
            $this->hash($second),
            'Identical requests must hit the same cache entry',
        );
    }

    #[Test]
    public function ignoresQueryParametersNoContextIsBuiltOn(): void
    {
        $plain = $this->dispatch($this->createRequest())
            ->getPageCacheIdentifierParameters();
        $withUnrelated = $this->dispatch($this->createRequest(['utm_source' => 'newsletter']))
            ->getPageCacheIdentifierParameters();

        self::assertSame(
            $this->hash($plain),
            $this->hash($withUnrelated),
            'An unrelated GET parameter must not fragment the page cache',
        );
    }

    /**
     * @param array<string, mixed> $identifierParameters
     */
    private function dispatch(
        ServerRequestInterface $request,
        array $identifierParameters = [],
    ): BeforePageCacheIdentifierIsHashedEvent {
        $event = new BeforePageCacheIdentifierIsHashedEvent($request, $identifierParameters);

        $listener = new PageCacheIdentifierEventListener(
            new PageService(),
            $this->createQueryParameterService(),
        );

        $listener($event);

        return $event;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function createRequest(array $queryParams = []): ServerRequestInterface
    {
        return (new ServerRequest('https://example.com/'))
            ->withQueryParams($queryParams);
    }

    /**
     * The way the core turns the identifier parameters into the cache
     * identifier - see PrepareTypoScriptFrontendRendering::createPageCacheIdentifier().
     *
     * @param array<array-key, mixed> $parameters
     */
    private function hash(array $parameters): string
    {
        return hash('xxh3', serialize($parameters));
    }

    /**
     * A service reporting a single "channel" query parameter context, without
     * touching the database.
     */
    private function createQueryParameterService(): QueryParameterService
    {
        $context = new class extends QueryParameterContext {
            public function __construct()
            {
            }

            protected function getConfValue(
                string $fieldNameArg,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                return $fieldNameArg === 'field_name' ? 'channel' : $default;
            }
        };

        return new class ($context) extends QueryParameterService {
            public function __construct(private readonly QueryParameterContext $mockContext)
            {
            }

            /**
             * @return list<AbstractContext>
             */
            protected function getAvailableContexts(): array
            {
                return [$this->mockContext];
            }
        };
    }
}
