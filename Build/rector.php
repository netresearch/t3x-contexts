<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/../Classes',
        __DIR__ . '/../Configuration',
        __DIR__ . '/../Tests',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/../ext_emconf.php',
        __DIR__ . '/../.Build',
        __DIR__ . '/../vendor',
    ]);

    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();

    // Define what rule sets will be applied - upgrade to PHP 8.2 and TYPO3 v13
    $rectorConfig->sets([
        // PHP level upgrades
        LevelSetList::UP_TO_PHP_82,

        // TYPO3 v12 migrations only (extension supports ^12.4 || ^13.4)
        // Note: Don't use UP_TO_TYPO3_13 as it introduces v13-only APIs
        Typo3LevelSetList::UP_TO_TYPO3_12,

        // TYPO3 code quality and general improvements
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
    ]);

    // Skip some rules that may cause issues or require manual review
    $rectorConfig->skip([
        // Skip constructor promotion - keep explicit property declarations for clarity
        ClassPropertyAssignToConstructorPromotionRector::class,

        // Skip removing parent calls - may be needed for TYPO3 hooks
        RemoveParentCallWithoutParentRector::class,
    ]);
};
