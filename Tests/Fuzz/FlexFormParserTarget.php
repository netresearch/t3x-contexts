<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * Fuzzing target for FlexForm XML parsing in AbstractContext.
 *
 * Tests getConfValue() with random/mutated XML inputs to find crashes,
 * memory exhaustion, or unexpected exceptions in FlexForm parsing.
 */

declare(strict_types=1);

use Netresearch\Contexts\Context\AbstractContext;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Utility\GeneralUtility;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Initialize TYPO3 cache manager required by GeneralUtility::xml2array()
$cacheManager = new CacheManager();
$cacheManager->setCacheConfigurations([
    'runtime' => [
        'frontend' => VariableFrontend::class,
        'backend' => TransientMemoryBackend::class,
        'options' => [],
        'groups' => [],
    ],
]);
GeneralUtility::setSingletonInstance(
    CacheManager::class,
    $cacheManager,
);

/**
 * Testable context implementation for fuzzing. AbstractContext parses
 * "type_conf" in its constructor, so every input gets a new instance.
 */
$contextClass = (new class extends AbstractContext {
    public function match(array $arDependencies = []): bool
    {
        return true;
    }

    public function fuzzGetConfValue(string $field): string
    {
        return $this->getConfValue($field);
    }
})::class;

$createContext = static fn(string $typeConf): object => new $contextClass([
    'uid' => 1,
    'pid' => 0,
    'type' => 'fuzz',
    'title' => 'Fuzz Test',
    'alias' => 'fuzz',
    'type_conf' => $typeConf,
    'invert' => 0,
    'use_session' => 0,
    'disabled' => 0,
    'hide_in_backend' => 0,
    'tstamp' => time(),
]);

/** @var PhpFuzzer\Config $config */
$config->setTarget(function (string $input) use ($createContext): void {
    // Wrap XML in FlexForm structure
    $xml = '<?xml version="1.0" encoding="utf-8"?>'
        . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
        . '<field index="fuzzField"><value index="vDEF">' . $input . '</value></field>'
        . '</language></sheet></data></T3FlexForms>';

    // Try to parse and retrieve the value
    try {
        $createContext($xml)->fuzzGetConfValue('fuzzField');
    } catch (Throwable) {
        // Ignore parsing errors - we're looking for crashes
    }

    // Also test with raw malformed XML
    try {
        $createContext($input)->fuzzGetConfValue('anyField');
    } catch (Throwable) {
        // Ignore parsing errors
    }
});

$config->setMaxLen(65536);
