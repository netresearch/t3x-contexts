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

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Service\PageService;
use Netresearch\Contexts\Service\QueryParameterService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\BeforePageCacheIdentifierIsHashedEvent;

/**
 * Makes the page cache identifier depend on the active contexts.
 *
 * Without this, all context variants of a page share a single page cache entry
 * and the variant that happens to be rendered first is served to everyone -
 * the ContextRestriction filters records per request, but the resulting HTML is
 * cached per page.
 *
 * Replaces the SC_OPTIONS['tslib/class.tslib_fe.php']['createHashBase'] hook
 * that was removed in TYPO3 v13.0 (#102932).
 */
#[AsEventListener(
    identifier: 'contexts/page-cache-identifier',
    event: BeforePageCacheIdentifierIsHashedEvent::class,
)]
final readonly class PageCacheIdentifierEventListener
{
    public function __construct(
        private PageService $pageService,
        private QueryParameterService $queryParameterService,
    ) {
    }

    public function __invoke(BeforePageCacheIdentifierIsHashedEvent $event): void
    {
        $parameters = $event->getPageCacheIdentifierParameters();

        $parameters['tx_contexts'] = [
            // Active context combination - the reason the rendered page differs.
            'contexts' => $this->pageService->getHashString(),
            // Values of the GET parameters the contexts are built on. Needed
            // because these parameters are usually excluded from the cHash and
            // are carried into generated links via config.linkVars.
            'parameters' => $this->queryParameterService->getParameterValues($event->getRequest()),
        ];

        $event->setPageCacheIdentifierParameters($parameters);
    }
}
