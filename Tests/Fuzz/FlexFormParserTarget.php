<?php

/**
 * Fuzzing target for FlexForm XML parsing in AbstractContext.
 *
 * Tests getConfValue() with random/mutated XML inputs to find crashes,
 * memory exhaustion, or unexpected exceptions in FlexForm parsing.
 */

declare(strict_types=1);

use Netresearch\Contexts\Context\AbstractContext;

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

// Initialize TYPO3 cache manager required by GeneralUtility::xml2array()
$cacheManager = new TYPO3\CMS\Core\Cache\CacheManager();
$cacheManager->setCacheConfigurations([
    'runtime' => [
        'frontend' => TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
        'backend' => TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
        'options' => [],
        'groups' => [],
    ],
]);
TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
    TYPO3\CMS\Core\Cache\CacheManager::class,
    $cacheManager,
);

/**
 * Testable context implementation for fuzzing.
 */
$contextClass = new class ([
    'uid' => 1,
    'pid' => 0,
    'type' => 'fuzz',
    'title' => 'Fuzz Test',
    'alias' => 'fuzz',
    'type_conf' => '',
    'invert' => 0,
    'use_session' => 0,
    'disabled' => 0,
    'hide_in_backend' => 0,
    'tstamp' => time(),
]) extends AbstractContext {
    public function match(array $arDependencies = []): bool
    {
        return true;
    }

    public function setTypeConf(string $xml): void
    {
        // Use reflection to set the type_conf and trigger re-parsing
        $reflection = new ReflectionClass(AbstractContext::class);
        $arRowProp = $reflection->getProperty('arRow');
        $arRowProp->setAccessible(true);

        $arRow = $arRowProp->getValue($this);
        $arRow['type_conf'] = $xml;
        $arRowProp->setValue($this, $arRow);

        // Reset the parsed config
        $arFlexProp = $reflection->getProperty('arFlex');
        $arFlexProp->setAccessible(true);
        $arFlexProp->setValue($this, null);
    }

    public function fuzzGetConfValue(string $field): string
    {
        return $this->getConfValue($field);
    }
};

/** @var PhpFuzzer\Config $config */
$config->setTarget(function (string $input) use ($contextClass): void {
    // Wrap XML in FlexForm structure
    $xml = '<?xml version="1.0" encoding="utf-8"?>' .
        '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">' .
        '<field index="fuzzField"><value index="vDEF">' . $input . '</value></field>' .
        '</language></sheet></data></T3FlexForms>';

    $contextClass->setTypeConf($xml);

    // Try to parse and retrieve the value
    try {
        $contextClass->fuzzGetConfValue('fuzzField');
    } catch (Throwable) {
        // Ignore parsing errors - we're looking for crashes
    }

    // Also test with raw malformed XML
    $contextClass->setTypeConf($input);
    try {
        $contextClass->fuzzGetConfValue('anyField');
    } catch (Throwable) {
        // Ignore parsing errors
    }
});

$config->setMaxLen(65536);
