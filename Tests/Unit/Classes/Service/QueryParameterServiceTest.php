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

namespace Netresearch\Contexts\Tests\Unit\Service;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Netresearch\Contexts\Service\QueryParameterService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for QueryParameterService.
 *
 * The service is the single source of truth for "which GET parameters does this
 * extension make the rendering depend on". It replaces the static state of the
 * removed FrontendControllerService::registerQueryParameter().
 */
final class QueryParameterServiceTest extends UnitTestCase
{
    #[Test]
    public function implementsSingletonInterface(): void
    {
        self::assertInstanceOf(SingletonInterface::class, new QueryParameterService());
    }

    #[Test]
    public function collectsTheParameterNamesOfAllQueryParameterContexts(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('affID'),
        ]);

        self::assertSame(['affID', 'channel'], $service->getParameterNames());
    }

    #[Test]
    public function ignoresContextsOfOtherTypes(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            new DomainContext(),
        ]);

        self::assertSame(['channel'], $service->getParameterNames());
    }

    #[Test]
    public function ignoresDisabledContexts(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('disabled_one', true, true),
        ]);

        self::assertSame(['channel'], $service->getParameterNames());
    }

    #[Test]
    public function ignoresContextsWithoutAParameterName(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext(''),
        ]);

        self::assertSame([], $service->getParameterNames());
    }

    #[Test]
    public function reportsTheSameParameterOnlyOnce(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('channel'),
        ]);

        self::assertSame(['channel'], $service->getParameterNames());
    }

    #[Test]
    public function linkVarNamesExcludeSessionBackedContexts(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('remembered', false),
        ]);

        self::assertSame(
            ['channel', 'remembered'],
            $service->getParameterNames(),
            'Both parameters influence the rendering',
        );
        self::assertSame(
            ['channel'],
            $service->getLinkVarNames(),
            'A session backed context does not need the parameter in every link',
        );
    }

    #[Test]
    public function parameterValuesOnlyContainParametersPresentInTheRequest(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('affID'),
        ]);

        $request = (new ServerRequest('https://example.com/'))
            ->withQueryParams(['channel' => 'mobile', 'unrelated' => 'x']);

        self::assertSame(['channel' => 'mobile'], $service->getParameterValues($request));
    }

    #[Test]
    public function parameterValuesAreOrderIndependent(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
            $this->createQueryParameterContext('affID'),
        ]);

        $first = $service->getParameterValues(
            (new ServerRequest('https://example.com/'))
                ->withQueryParams(['channel' => 'mobile', 'affID' => '42']),
        );
        $second = $service->getParameterValues(
            (new ServerRequest('https://example.com/'))
                ->withQueryParams(['affID' => '42', 'channel' => 'mobile']),
        );

        self::assertSame($first, $second, 'The same URL parameters must produce the same array');
    }

    #[Test]
    public function parameterValuesDifferPerParameterValue(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
        ]);

        self::assertNotSame(
            $service->getParameterValues(
                (new ServerRequest('https://example.com/'))->withQueryParams(['channel' => 'mobile']),
            ),
            $service->getParameterValues(
                (new ServerRequest('https://example.com/'))->withQueryParams(['channel' => 'desktop']),
            ),
        );
    }

    #[Test]
    public function resetDropsTheCachedParameterNames(): void
    {
        $service = $this->createService([
            $this->createQueryParameterContext('channel'),
        ]);

        self::assertSame(['channel'], $service->getParameterNames());

        $service->reset();

        self::assertSame(['channel'], $service->getParameterNames(), 'Reloads from the contexts');
    }

    /**
     * @param list<AbstractContext> $contexts
     */
    private function createService(array $contexts): QueryParameterService
    {
        return new class ($contexts) extends QueryParameterService {
            /**
             * @param list<AbstractContext> $mockContexts
             */
            public function __construct(private readonly array $mockContexts)
            {
            }

            /**
             * @return list<AbstractContext>
             */
            protected function getAvailableContexts(): array
            {
                return $this->mockContexts;
            }
        };
    }

    private function createQueryParameterContext(
        string $parameterName,
        bool $addToLinkVars = true,
        bool $disabled = false,
    ): QueryParameterContext {
        return new class ($parameterName, $addToLinkVars, $disabled) extends QueryParameterContext {
            public function __construct(
                private readonly string $mockParameterName,
                bool $addToLinkVars,
                bool $disabled,
            ) {
                // "use_session" is the inverse of "carry the parameter in every link"
                $this->use_session = !$addToLinkVars;
                $this->disabled = $disabled;
            }

            protected function getConfValue(
                string $fieldNameArg,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                return $fieldNameArg === 'field_name' ? $this->mockParameterName : $default;
            }
        };
    }
}
