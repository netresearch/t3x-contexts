<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Service\IconService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

/**
 * PSR-14 event listener for modifying icon overlays based on context.
 * Replaces the legacy SC_OPTIONS[IconFactory::class]['overrideIconOverlay'] hook.
 */
#[AsEventListener(
    identifier: 'contexts/icon-overlay-modifier',
    event: ModifyRecordOverlayIconIdentifierEvent::class,
)]
final readonly class IconOverlayEventListener
{
    public function __construct(
        private IconService $iconService,
    ) {
    }

    public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
    {
        $iconOverlay = $event->getOverlayIconIdentifier();
        $table = $event->getTable();
        $row = $event->getRow();
        $status = $event->getStatus();

        if ($table !== '' && $row !== []) {
            $modifiedOverlay = $this->iconService->postOverlayPriorityLookup(
                $table,
                $row,
                $status,
                $iconOverlay,
            );

            if ($modifiedOverlay !== $iconOverlay) {
                $event->setOverlayIconIdentifier($modifiedOverlay);
            }
        }
    }
}
