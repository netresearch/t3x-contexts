<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    static function () {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
            'tx_contexts_contexts.type_conf.combination',
            'EXT:contexts/Resources/Private/csh/Combination.xml'
        );

        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages(
            'tx_contexts_contexts'
        );

        $GLOBALS['TBE_STYLES']['skins']['contexts'] = [
            'name' => 'contexts',
            'stylesheetDirectories' => [
                'css' => 'EXT:contexts/Resources/Public/StyleSheet'
            ]
        ];
    }
);
