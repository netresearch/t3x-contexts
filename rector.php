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
use Rector\Php73\Rector\ConstFetch\SensitiveConstantNameRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php80\Rector\FunctionLike\MixedTypeRector;
use Rector\Php80\Rector\FunctionLike\UnionTypesRector;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Rector\v11\v0\ExtbaseControllerActionsMustReturnResponseInterfaceRector;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/Classes',
        __DIR__ . '/Configuration',
        __DIR__ . '/Resources',
        'ext_*',
    ]);

    $rectorConfig->skip([
        'ext_emconf.php',
        'ext_*.sql',
    ]);

    $rectorConfig->phpstanConfig('phpstan.neon');
    $rectorConfig->importNames();
    $rectorConfig->removeUnusedImports();
    $rectorConfig->disableParallel();

    // Define what rule sets will be applied
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_74,
        Typo3LevelSetList::UP_TO_TYPO3_11,

        Typo3SetList::UNDERSCORE_TO_NAMESPACE,
        Typo3SetList::DATABASE_TO_DBAL,
        Typo3SetList::EXTBASE_COMMAND_CONTROLLERS_TO_SYMFONY_COMMANDS,
        Typo3SetList::REGISTER_ICONS_TO_ICON,
    ]);

    // Skip some rules
    $rectorConfig->skip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ExtbaseControllerActionsMustReturnResponseInterfaceRector::class,
        MixedTypeRector::class,
        SensitiveConstantNameRector::class,
        UnionTypesRector::class,
        RemoveParentCallWithoutParentRector::class,
    ]);
};
