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

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    'extensions-contexts-status-overlay-contexts' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:contexts/Resources/Public/Icons/overlay-contexts.png',
    ],
];
