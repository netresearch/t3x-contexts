<?php

/**
 * Fuzz test for IP address matching logic.
 *
 * Run with: vendor/bin/php-fuzzer fuzz Tests/Fuzz/IpMatchingFuzz.php
 */

declare(strict_types=1);

use Netresearch\Contexts\Service\Matcher\IpMatcher;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * Fuzz the IP range matching logic to find edge cases and potential crashes.
 *
 * @param string $data Random fuzz input
 */
return function (string $data): void {
    // Skip empty input
    if (strlen($data) === 0) {
        return;
    }

    // Split input into potential IP and range parts
    $parts = explode("\n", $data, 2);
    $testIp = $parts[0] ?? '';
    $ipRange = $parts[1] ?? '';

    // Test various IP matching scenarios
    try {
        // Test with simulated REMOTE_ADDR
        $_SERVER['REMOTE_ADDR'] = $testIp;

        // If class exists, test the matcher
        if (class_exists(IpMatcher::class)) {
            $matcher = new IpMatcher();
            // Test match with fuzzed IP and range
            $matcher->match($testIp, $ipRange);
        }
    } catch (TypeError $e) {
        // Type errors are expected for malformed input
    } catch (ValueError $e) {
        // Value errors are expected for invalid ranges
    } catch (InvalidArgumentException $e) {
        // Invalid argument exceptions are expected
    } finally {
        // Clean up
        unset($_SERVER['REMOTE_ADDR']);
    }
};
