<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

defined('TYPO3_MODE') or die();

/**
 * TCA override for tt_content table
 */
call_user_func(
    static function () {
        \Netresearch\Contexts\Api\Configuration::enableContextsForTable(
            'contexts',
            'tt_content'
        );
    }
);
