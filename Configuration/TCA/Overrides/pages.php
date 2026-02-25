<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Netresearch\Contexts\Api\Configuration;

defined('TYPO3') || die('Access denied.');

/**
 * TCA override for pages table
 */
call_user_func(static function (): void {
    Configuration::enableContextsForTable(
        'contexts',
        'pages',
        [
            'tx_contexts_nav' => [
                'label' => 'LLL:' . Configuration::LANG_FILE . ':tx_contexts_menu_visibility',
                'flatten' => true,
            ],
        ],
    );
});
