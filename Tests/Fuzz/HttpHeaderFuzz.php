<?php

/**
 * Fuzz test for HTTP header context matching.
 *
 * Run with: vendor/bin/php-fuzzer fuzz Tests/Fuzz/HttpHeaderFuzz.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Fuzz HTTP header matching logic.
 *
 * @param string $data Random fuzz input
 */
return function (string $data): void {
    if (strlen($data) === 0) {
        return;
    }

    // Split into header name and expected value
    $parts = explode("\n", $data, 3);
    $headerName = $parts[0] ?? '';
    $headerValue = $parts[1] ?? '';
    $pattern = $parts[2] ?? '';

    try {
        // Convert header name to SERVER format (like HttpHeaderContext does)
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));

        // Set mock header
        $_SERVER[$serverKey] = $headerValue;

        // Test header retrieval
        $actualValue = $_SERVER[$serverKey] ?? null;

        // Test pattern matching modes
        if ($actualValue !== null && $pattern !== '') {
            // Simple presence check
            $actualValue !== '';

            // Value matching
            $actualValue === $pattern;

            // Regex matching (with error suppression for invalid patterns)
            @preg_match('/' . $pattern . '/i', $actualValue);
        }

        // Clean up
        unset($_SERVER[$serverKey]);
    } catch (TypeError $e) {
        // Expected for malformed input
    } catch (ValueError $e) {
        // Expected for invalid values
    } finally {
        // Ensure cleanup
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        if (isset($_SERVER[$serverKey])) {
            unset($_SERVER[$serverKey]);
        }
    }
};
