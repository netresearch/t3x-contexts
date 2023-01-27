<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */


/**
 * TCA override for tt_content table
 */

defined('TYPO3_MODE') or die();

call_user_func(
    static function () {
        \Netresearch\Contexts\Api\Configuration::enableContextsForTable(
            'contexts',
            'tt_content'
        );
    }
);
