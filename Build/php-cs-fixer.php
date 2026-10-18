<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * PHP-CS-Fixer configuration for TYPO3 extension.
 *
 * The rules, the finder and the license header come from the shared
 * factory of netresearch/typo3-ci-workflows. The extra rules below are the
 * risky strictness rules of the previous local config that the shared set
 * does not contain.
 *
 * @see https://cs.symfony.com/doc/rules/index.html
 */

declare(strict_types=1);

$createConfig = require __DIR__ . '/../vendor/netresearch/typo3-ci-workflows/config/php-cs-fixer/config.php';

return $createConfig(
    "Copyright (c) 2025-2026 Netresearch DTT GmbH\nSPDX-License-Identifier: AGPL-3.0-or-later",
    __DIR__ . '/..',
    [
        '@PHP8x0Migration:risky' => true,

        // Strict typing
        'strict_comparison' => true,
        'strict_param'      => true,

        // PHPUnit
        'php_unit_construct'                     => true,
        'php_unit_dedicate_assert'               => ['target' => 'newest'],
        'php_unit_expectation'                   => ['target' => 'newest'],
        'php_unit_mock'                          => ['target' => 'newest'],
        'php_unit_mock_short_will_return'        => true,
        'php_unit_set_up_tear_down_visibility'   => true,
        'php_unit_strict'                        => true,
        'php_unit_test_case_static_method_calls' => ['call_type' => 'self'],
    ],
);
