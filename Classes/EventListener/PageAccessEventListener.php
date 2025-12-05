<?php

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
        $pageInformation = $event->getPageInformation();
        $pageRecord = $pageInformation->getPageRecord();

        if ($pageRecord === []) {
            return;
        }

        $rootLine = $pageInformation->getRootLine();

        // Check if page is accessible based on context
        $result = $this->frontendControllerService->checkEnableFieldsForRootLine($rootLine);

        // If the page is not accessible, deny access
        if ($result === false) {
            $response = GeneralUtility::makeInstance(ErrorController::class)
                ->accessDeniedAction(
                    $event->getRequest(),
                    'Page is not accessible in current context',
                );
            throw new ImmediateResponseException($response, 7536329338);
        }
    }
}
