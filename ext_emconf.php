<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

$EM_CONF['contexts'] = [
    'title'          => 'Multi-channel contexts',
    'description'    => 'Multi-channel content visibility for TYPO3 - by Netresearch',
    'category'       => 'misc',
    'author'         => 'Andre Hähnel, Christian Opitz, Christian Weiske, Marian Pollzien, Rico Sonntag, Benni Mack',
    'author_email'   => 'typo3@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
    'state'          => 'stable',
    'version'        => '4.0.0',
    'constraints'    => [
        'depends'   => [
            'php'   => '8.2.0-8.4.99',
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests'  => [
            'contexts_geolocation' => '',
        ],
    ],
];
