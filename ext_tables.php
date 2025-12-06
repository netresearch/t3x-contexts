<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') || die('Access denied.');

(static function (): void {
    // allowTableOnStandardPages() was removed in TYPO3 v13
    // In v13+, use TCA ctrl.security.ignorePageTypeRestriction instead
    $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);
    if ($typo3Version->getMajorVersion() < 13) {
        // TYPO3 v12 and below
        ExtensionManagementUtility::allowTableOnStandardPages('tx_contexts_contexts');
    }
    // For v13+, this is configured in Configuration/TCA/tx_contexts_contexts.php
    // via 'security' => ['ignorePageTypeRestriction' => true]
})();
