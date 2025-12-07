<?php

/**
 * Fractor configuration for TYPO3 extension upgrade
 *
 * Handles non-PHP file migrations:
 * - FlexForms (XML)
 * - TypoScript
 * - YAML (Services.yaml)
 * - Fluid templates
 *
 * @see https://github.com/andreaswolf/fractor
 */

declare(strict_types=1);

use a9f\Fractor\Configuration\FractorConfiguration;
use a9f\Typo3Fractor\Set\Typo3LevelSetList;

return FractorConfiguration::configure()
    ->withPaths([
        __DIR__ . '/../Configuration',
        __DIR__ . '/../Resources',
    ])
    ->withSkip([
        __DIR__ . '/../.Build',
        __DIR__ . '/../vendor',
    ])
    ->withSets([
        // TYPO3 v12 migrations for FlexForms, TypoScript, YAML, Fluid
        // Note: Don't use UP_TO_TYPO3_13 if extension supports both ^12.4 || ^13.4
        Typo3LevelSetList::UP_TO_TYPO3_12,
    ]);
