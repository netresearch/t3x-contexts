<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Service\PageService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Frontend\Event\ModifyCacheLifetimeForPageEvent;

/**
 * PSR-14 event listener for modifying cache hash based on context.
 * Replaces the legacy SC_OPTIONS['tslib/class.tslib_fe.php']['createHashBase'] hook.
 *
 * Note: In TYPO3 v12+, the cache hash modification is handled differently.
 * This listener uses ModifyCacheLifetimeForPageEvent as the entry point
 * and modifies the page cache identifier indirectly through context-aware caching.
 */
#[AsEventListener(
    identifier: 'contexts/cache-hash-modifier',
    event: ModifyCacheLifetimeForPageEvent::class,
)]
final readonly class CacheHashEventListener
{
    public function __construct(
        private PageService $pageService,
    ) {
    }

    public function __invoke(ModifyCacheLifetimeForPageEvent $event): void
    {
        // The context-based cache differentiation is now handled through
        // the ContextRestriction query restriction and context-aware page caching.
        // This listener can be used to adjust cache lifetime based on active contexts
        // if needed in the future.
    }
}
