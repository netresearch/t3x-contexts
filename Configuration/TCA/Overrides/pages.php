<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

call_user_func(
    static function () {
        \Netresearch\Contexts\Api\Configuration::enableContextsForTable(
            'contexts',
            'pages',
            [
                'tx_contexts_nav' => [
                    'label' => 'LLL:' . \Netresearch\Contexts\Api\Configuration::LANG_FILE . ':tx_contexts_menu_visibility',
                    'flatten' => true
                ]
            ]
        );
    }
);
