<?php

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
    if (strlen($data) === 0) {
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
            // Exact match
            $actualValue === $expectedValue;

            // Case-insensitive match
            strtolower((string) $actualValue) === strtolower($expectedValue);

            // Regex match (with error suppression)
            @preg_match('/' . $expectedValue . '/i', (string) $actualValue);
        }

        // Clean up
        unset($_GET[$paramName], $_REQUEST[$paramName]);
    } catch (TypeError $e) {
        // Expected for malformed input
    } catch (ValueError $e) {
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
