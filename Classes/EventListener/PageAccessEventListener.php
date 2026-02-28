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

use Netresearch\Contexts\Service\FrontendControllerService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Event\AfterPageAndLanguageIsResolvedEvent;

/**
 * PSR-14 event listener for context-based page access control.
 * Replaces the legacy SC_OPTIONS['tslib/class.tslib_fe.php']['hook_checkEnableFields'] hook.
 */
#[AsEventListener(
    identifier: 'contexts/page-access-check',
    event: AfterPageAndLanguageIsResolvedEvent::class,
)]
final readonly class PageAccessEventListener
{
    public function __construct(
        private FrontendControllerService $frontendControllerService,
    ) {
    }

    public function __invoke(AfterPageAndLanguageIsResolvedEvent $event): void
    {
        // TYPO3 13+: getPageInformation(), TYPO3 12: getController()
        if (method_exists($event, 'getPageInformation')) {
            $pageInformation = $event->getPageInformation();
            $pageRecord = $pageInformation->getPageRecord(); // @phpstan-ignore method.nonObject (TYPO3 12: PageInformation class does not exist)

            if ($pageRecord === []) {
                return;
            }

            $rootLine = $pageInformation->getRootLine(); // @phpstan-ignore method.nonObject
        } else {
            // @codeCoverageIgnoreStart
            /** @var TypoScriptFrontendController $controller */
            $controller = $event->getController(); // @phpstan-ignore method.notFound
            $pageRecord = $controller->page ?? [];

            if ($pageRecord === []) {
                return;
            }

            $rootLine = $controller->rootLine ?? [];
            // @codeCoverageIgnoreEnd
        }

        // Check if page is accessible based on context
        $result = $this->frontendControllerService->checkEnableFieldsForRootLine($rootLine); // @phpstan-ignore argument.type

        // If the page is not accessible, deny access
        if ($result === false) {
            $response = GeneralUtility::makeInstance(ErrorController::class)
                ->accessDeniedAction(
                    $event->getRequest(),
                    'Page is not accessible in current context',
                );
            // @codeCoverageIgnoreStart
            throw new ImmediateResponseException($response, 7536329338);
            // @codeCoverageIgnoreEnd
        }
    }
}
