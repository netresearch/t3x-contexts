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

use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\DeadCode\Rector\Concat\RemoveConcatAutocastRector;
use Rector\DeadCode\Rector\StaticCall\RemoveParentCallWithoutParentRector;
use Ssch\TYPO3Rector\CodeQuality\General\InjectMethodToConstructorInjectionRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;
use Ssch\TYPO3Rector\TYPO314\v0\MigrateLabelReferenceToDomainSyntaxRector;

$configure = require_once __DIR__ . '/../vendor/netresearch/typo3-ci-workflows/config/rector/rector.php';

return static function (RectorConfig $rectorConfig) use ($configure): void {
    // Shared org base config: paths, code-quality sets, rule skips,
    // and the package's ergebnis-free phpstan-rector.neon.
    $configure($rectorConfig, __DIR__ . '/..');

    // paths() replaces the shared list — re-declared to keep Tests/ in scope,
    // which the shared $projectRoot default leaves out.
    $rectorConfig->paths(array_merge(
        [
            __DIR__ . '/../Classes',
            __DIR__ . '/../Configuration',
            __DIR__ . '/../Resources',
            __DIR__ . '/../Tests',
        ],
        glob(__DIR__ . '/../ext_*.php') ?: [],
    ));

    $rectorConfig->sets([
        // TYPO3 v14 migrations (extension supports ^13.4 || ^14.3).
        //
        // UP_TO_TYPO3_14 is UP_TO_TYPO3_13 plus the TYPO3_14 set. The only
        // rules in that set that would emit v14-only API are the class renames
        // for upgrade wizards (TYPO3\CMS\Install\Updates\* -> Core\Upgrades\*)
        // and for Extbase annotations (Extbase\Annotation -> Extbase\Attribute).
        // This extension ships neither an upgrade wizard nor any Extbase code,
        // so nothing can be rewritten to a class that TYPO3 v13.4 does not have.
        // Everything else in the set is TCA cleanup and removal of APIs that no
        // longer exist in v14 either way.
        Typo3LevelSetList::UP_TO_TYPO3_14,

        // TYPO3 code quality and general improvements
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
    ]);

    $rectorConfig->skip([
        // Skip removing (string) casts in concatenation — PHPStan level 10 requires
        // explicit casts when concatenating mixed values (from $arRow, $GLOBALS, etc.)
        RemoveConcatAutocastRector::class,

        // Skip removing parent calls - may be needed for TYPO3 hooks
        RemoveParentCallWithoutParentRector::class,

        // Skip removing unused event listener parameters - PSR-14 listeners must accept the event
        RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__ . '/../Classes/EventListener',
        ],

        // Skip inject-to-constructor conversion for RecordSettingsFormElement
        // because TYPO3 13 AbstractFormElement declares protected $iconFactory
        // which conflicts with constructor promotion
        InjectMethodToConstructorInjectionRector::class => [
            __DIR__ . '/../Classes/Form/RecordSettingsFormElement.php',
        ],

        // The only rule of the TYPO3 v14 set that may not be applied while the
        // extension still supports TYPO3 v13.4: translation domain syntax
        // ("contexts.db:key" instead of "LLL:EXT:contexts/.../locallang_db.xlf:key")
        // was introduced with TYPO3 v14 (#93334) and is not resolvable on v13.4.
        // Remove this skip once TYPO3 v13.4 support is dropped.
        MigrateLabelReferenceToDomainSyntaxRector::class,
    ]);
};
