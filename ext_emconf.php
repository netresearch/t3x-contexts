<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Multi-channel contexts',
    'description' => 'Contexts for TYPO3 - can be used to modify page behaviour in the frontend based on several conditions',
    'category' => 'misc',
    'author' => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag, Benni Mack',
    'author_email' => 'typo3.org@netresearch.de',
    'priority' => 'top',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author_company' => 'Netresearch GmbH & Co.KG',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.0.0-11.5.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
            'contexts_geolocation' => '',
        ],
    ],
    'suggests' => [
    ],
];
