<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Fuzz test for query parameter context matching.
 *
 * Run with: vendor/bin/php-fuzzer fuzz Tests/Fuzz/QueryParameterFuzz.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Fuzz query parameter matching logic.
 *
 * @param string $data Random fuzz input
 */
return function (string $data): void {
    if ($data === '') {
        return;
    }

    // Split into parameter name and value
    $parts = explode("\n", $data, 3);
    $paramName = $parts[0] ?? '';
    $paramValue = $parts[1] ?? '';
    $expectedValue = $parts[2] ?? '';

    try {
        // Simulate GET parameter
        $_GET[$paramName] = $paramValue;
        $_REQUEST[$paramName] = $paramValue;

        // Test parameter presence check (like QueryParameterContext does)
        $actualValue = $_GET[$paramName] ?? null;

        // Test value comparison modes
        if ($actualValue !== null) {
            strtolower($expectedValue);
            // Case-insensitive match
            strtolower($actualValue);
            // Regex match (with error suppression)
            @preg_match('/' . $expectedValue . '/i', $actualValue);
        }

        // Clean up
        unset($_GET[$paramName], $_REQUEST[$paramName]);
    } catch (TypeError) {
        // Expected for malformed input
    } catch (ValueError) {
        // Expected for invalid values
    } finally {
        // Ensure cleanup
        if (isset($_GET[$paramName])) {
            unset($_GET[$paramName]);
        }
        if (isset($_REQUEST[$paramName])) {
            unset($_REQUEST[$paramName]);
        }
    }
};
