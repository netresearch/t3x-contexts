<?php

/**
 * Fuzzing target for LogicalExpressionEvaluator.
 *
 * Tests expression parsing with random/mutated inputs to find crashes,
 * infinite loops, or unexpected exceptions in the combination context parser.
 */

declare(strict_types=1);

use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

/** @var PhpFuzzer\Config $config */
$evaluator = new LogicalExpressionEvaluator();

// Create mock contexts for evaluation
$mockContexts = [
    'ctx1' => true,
    'ctx2' => false,
    'ctx3' => true,
    'test' => true,
    'domain' => false,
];

$config->setTarget(function (string $input) use ($evaluator, $mockContexts): void {
    try {
        // Test expression parsing
        $evaluator->parse($input);

        // Test evaluation with mock contexts
        $evaluator->run($mockContexts, $input);
    } catch (Throwable) {
        // Ignore parsing/evaluation errors - we're looking for crashes
    }
});

$config->setMaxLen(4096);
