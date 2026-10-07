<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Fuzzing target for LogicalExpressionEvaluator.
 *
 * Tests expression parsing with random/mutated inputs to find crashes,
 * infinite loops, or unexpected exceptions in the combination context parser.
 */

declare(strict_types=1);

use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/** @var PhpFuzzer\Config $config */

// Create mock contexts for evaluation
$mockContexts = [
    'ctx1' => true,
    'ctx2' => false,
    'ctx3' => true,
    'test' => true,
    'domain' => false,
];

$config->setTarget(function (string $input) use ($mockContexts): void {
    try {
        // Tokenize, parse and evaluate with mock contexts
        LogicalExpressionEvaluator::run($input, $mockContexts);
    } catch (LogicalExpressionEvaluatorException) {
        // A rejected expression is a valid outcome - we're looking for crashes
    }
});

$config->setMaxLen(4096);
