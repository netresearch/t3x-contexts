<?php

/**
 * Fuzz test for domain matching logic.
 *
 * Run with: vendor/bin/php-fuzzer fuzz Tests/Fuzz/DomainMatchingFuzz.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Fuzz the domain matching logic to find edge cases.
 *
 * @param string $data Random fuzz input
 */
return function (string $data): void {
    if (strlen($data) === 0) {
        return;
    }

    // Split into host and pattern
    $parts = explode("\n", $data, 2);
    $host = $parts[0] ?? '';
    $pattern = $parts[1] ?? '';

    try {
        // Test domain matching with wildcards (simulating DomainContext logic)
        $patterns = preg_split('/[\r\n]+/', $pattern, -1, \PREG_SPLIT_NO_EMPTY);

        foreach ($patterns as $domainPattern) {
            $domainPattern = trim($domainPattern);
            if ($domainPattern === '') {
                continue;
            }

            // Convert wildcard pattern to regex (like DomainContext does)
            $regex = '/^' . str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote($domainPattern, '/')) . '$/i';

            // Suppress regex errors for malformed patterns
            @preg_match($regex, $host);
        }
    } catch (TypeError $e) {
        // Expected for malformed input
    } catch (ValueError $e) {
        // Expected for invalid values
    }
};
