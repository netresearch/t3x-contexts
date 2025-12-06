<?php

/**
 * Fuzzing target for IP address matching in IpContext.
 *
 * Tests IP validation and matching with random/mutated inputs to find
 * crashes or unexpected behavior in IP address handling.
 */

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/** @var PhpFuzzer\Config $config */
$config->setTarget(function (string $input): void {
    try {
        // Test IP validation using GeneralUtility methods
        GeneralUtility::validIP($input);

        // Test CIDR matching
        GeneralUtility::cmpIPv4($input, '127.0.0.1');
        GeneralUtility::cmpIPv6($input, '::1');

        // Test IP normalization
        GeneralUtility::normalizeIPv6($input);

        // Test comma-separated IP list
        $ipList = explode(',', $input);
        foreach ($ipList as $ip) {
            GeneralUtility::validIP(trim($ip));
        }
    } catch (Throwable) {
        // Ignore validation errors - we're looking for crashes
    }
});

$config->setMaxLen(1024);
