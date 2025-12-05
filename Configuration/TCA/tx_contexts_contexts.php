<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

defined('TYPO3') || die('Access denied.');

/**
 * TCA for tx_contexts_contexts table
 */
return [
    'ctrl' => [
        'title' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'ORDER BY title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
        ],
        'adminOnly' => true,
        'rootLevel' => -1,
        'iconfile' => 'EXT:contexts/Resources/Public/Icons/tx_contexts_contexts.gif',
        // TYPO3 v13: Allow table on standard pages (replaces allowTableOnStandardPages())
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'disabled' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.disable',
            'config' => [
                'type' => 'check',
            ],
        ],
        'hide_in_backend' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.hide_in_backend',
            'config' => [
                'type' => 'check',
            ],
        ],
        'alias' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.alias',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'alphanum_x,nospace,unique,lower',
            ],
        ],
        'type' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                //                'items' => [
                //                    ['LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.type.select_type', '']
                //                ],
                'size' => 1,
                'maxitems' => 1,
            ],
            'onChange' => 'reload',
        ],
        'type_conf' => [
            'exclude' => false,
            'displayCond' => 'FIELD:type:REQ:true',
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.type_conf',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'type',
                'ds' => [],
            ],
        ],
        'invert' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.invert',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'use_session' => [
            'exclude' => false,
            'label' => 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.use_session',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ],
        ],
        'default_settings' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '--div--;' . 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.general'
                . ',title'
                . ',--palette--;' . 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.visibility;visibility'
                . ',alias,type,type_conf,invert,'
                . 'use_session'
                . ',--div--;' . 'LLL:EXT:contexts/Resources/Private/Language/locallang_db.xlf:tx_contexts_contexts.defaults',
        ],
    ],
    'palettes' => [
        'visibility' => [
            'showitem' => 'disabled,hide_in_backend',
            'canNotCollapse' => 1,
        ],
    ],
];
