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

namespace Netresearch\Contexts\Tests\Functional;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\EventListener\PageCacheIdentifierEventListener;
use Netresearch\Contexts\EventListener\TypoScriptConfigEventListener;
use Netresearch\Contexts\Service\PageService;
use Netresearch\Contexts\Service\QueryParameterService;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Regression tests for the TYPO3 v13/v14 migration of the frontend integration.
 *
 * Two things were silently broken before and are covered here end to end,
 * against real context records loaded from the database:
 *
 * 1. The session context read the frontend user from the removed
 *    TypoScriptFrontendController (#107831), so it never matched.
 * 2. Context aware cHash/linkVars handling was bound to the
 *    'createHashBase' and 'configArrayPostProc' hooks removed in v13.0
 *    (#102932), so all context variants of a page shared one cache entry and
 *    the switching GET parameter was dropped from generated links.
 */
final class ContextCachingAndSessionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Container::reset();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/contexts_session.csv');
    }

    protected function tearDown(): void
    {
        Container::reset();

        parent::tearDown();
    }

    #[Test]
    public function sessionContextMatchesWhenTheRequestCarriesTheSessionVariable(): void
    {
        $request = $this->createRequestWithFrontendUser(['nr_channel' => 'mobile']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $context = Container::get()->find('sessionchannel');

        self::assertNotNull(
            $context,
            'Session context must match when the frontend user session holds the variable',
        );
        self::assertSame('session', $context->getType());
    }

    #[Test]
    public function sessionContextDoesNotMatchWithoutTheSessionVariable(): void
    {
        $request = $this->createRequestWithFrontendUser([]);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        self::assertNull(
            Container::get()->find('sessionchannel'),
            'Session context must not match when the variable is absent',
        );
    }

    #[Test]
    public function sessionContextDoesNotMatchWithoutAFrontendUser(): void
    {
        Container::get()
            ->setRequest(new ServerRequest('https://example.com/'))
            ->initMatching();

        self::assertNull(
            Container::get()->find('sessionchannel'),
            'Without a frontend user there is no session to read',
        );
    }

    #[Test]
    public function pageCacheIdentifierDiffersBetweenContextVariantsOfTheSamePage(): void
    {
        $withoutChannel = $this->buildPageCacheIdentifier(
            new ServerRequest('https://example.com/'),
        );
        $withChannel = $this->buildPageCacheIdentifier(
            (new ServerRequest('https://example.com/'))->withQueryParams(['channel' => 'mobile']),
        );

        self::assertNotSame(
            $withoutChannel,
            $withChannel,
            'The two context variants of a page must not share one page cache entry',
        );
    }

    #[Test]
    public function pageCacheIdentifierIsStableForTheSameContextVariant(): void
    {
        $first = $this->buildPageCacheIdentifier(
            (new ServerRequest('https://example.com/'))->withQueryParams(['channel' => 'mobile']),
        );
        $second = $this->buildPageCacheIdentifier(
            (new ServerRequest('https://example.com/'))->withQueryParams(['channel' => 'mobile']),
        );

        self::assertSame($first, $second, 'Identical requests must hit the same page cache entry');
    }

    #[Test]
    public function theCacheIdentifierListenerIsRegisteredOnTheRealEventDispatcher(): void
    {
        $request = (new ServerRequest('https://example.com/'))
            ->withQueryParams(['channel' => 'mobile']);

        Container::get()
            ->setRequest($request)
            ->initMatching();

        $eventDispatcher = $this->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $event = new BeforePageCacheIdentifierIsHashedEvent($request, ['pageId' => 1]);
        $eventDispatcher->dispatch($event);

        self::assertArrayHasKey(
            'tx_contexts',
            $event->getPageCacheIdentifierParameters(),
            'The listener must be wired up by the #[AsEventListener] attribute alone',
        );
    }

    #[Test]
    public function configLinkVarsCarriesTheContextParameter(): void
    {
        $configTree = new RootNode();
        $linkVars = new ChildNode('linkVars');
        $linkVars->setValue('L');
        $configTree->addChild($linkVars);

        $event = new ModifyTypoScriptConfigEvent(
            new ServerRequest('https://example.com/'),
            new RootNode(),
            $configTree,
        );

        GeneralUtility::makeInstance(TypoScriptConfigEventListener::class)($event);

        self::assertSame(
            'L,channel',
            $event->getConfigTree()->getChildByName('linkVars')?->getValue(),
            'The GET parameter of the query parameter context must survive following a link',
        );
    }

    /**
     * The page cache identifier the core builds for the given request - see
     * PrepareTypoScriptFrontendRendering::createPageCacheIdentifier().
     */
    private function buildPageCacheIdentifier(ServerRequestInterface $request): string
    {
        Container::reset();
        Container::get()
            ->setRequest($request)
            ->initMatching();

        $event = new BeforePageCacheIdentifierIsHashedEvent($request, ['pageId' => 1]);

        // Built by hand instead of via makeInstance: PageService and
        // QueryParameterService are singletons and would keep the state of the
        // previous request within the same test.
        (new PageCacheIdentifierEventListener(new PageService(), new QueryParameterService()))($event);

        return hash('xxh3', serialize($event->getPageCacheIdentifierParameters()));
    }

    /**
     * A request carrying a real frontend user with an anonymous session, just
     * like the core FrontendUserAuthenticator middleware publishes it.
     *
     * @param array<string, mixed> $sessionData
     */
    private function createRequestWithFrontendUser(array $sessionData): ServerRequestInterface
    {
        $frontendUser = new FrontendUserAuthentication();
        $frontendUser->initializeUserSessionManager();
        $frontendUser->createAnonymousSession();

        foreach ($sessionData as $key => $value) {
            $frontendUser->setKey('ses', $key, $value);
        }

        return (new ServerRequest('https://example.com/'))
            ->withAttribute('frontend.user', $frontendUser);
    }
}
