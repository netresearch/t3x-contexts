<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Router script for PHP built-in server to handle TYPO3 rewrites
 *
 * This router simulates Apache/Nginx URL rewriting for TYPO3 clean URLs during E2E testing
 * with the PHP built-in server.
 *
 * Usage:
 *   php -S 0.0.0.0:8080 -t .Build/Web Build/Scripts/router.php
 *
 * The router:
 * 1. Serves static files (CSS, JS, images, etc.) directly
 * 2. Routes all other requests through TYPO3's index.php
 * 3. Preserves request URI for proper routing
 *
 * @package Netresearch\Contexts
 */

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . '/../../.Build/Web' . $path;

// Serve static files directly
if (is_file($file)) {
    return false;
}

// Route everything else through TYPO3 index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/../../.Build/Web/index.php';
require __DIR__ . '/../../.Build/Web/index.php';
