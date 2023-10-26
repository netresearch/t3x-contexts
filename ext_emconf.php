<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

$EM_CONF['contexts'] = [
    'title'          => 'Multi-channel contexts',
    'description'    => 'Contexts for TYPO3 - can be used to modify page behaviour in the frontend based on several conditions',
    'category'       => 'misc',
    'author'         => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag, Benni Mack',
    'author_email'   => 'typo3.org@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state'          => 'stable',
    'version'        => '3.0.0',
    'constraints'    => [
        'depends'   => [
            'typo3' => '11.5.0-11.99.99',
        ],
        'conflicts' => [
        ],
        'suggests'  => [
            'contexts_geolocation' => '',
        ],
    ],
];
