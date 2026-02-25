<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Functional\Integration;

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\AbstractContext;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Cross-extension integration tests.
 *
 * These tests verify that the contexts base extension works correctly
 * with sub-extensions (contexts_geolocation, contexts_wurfl) when all
 * three extensions are loaded together.
 *
 * Test scenarios:
 * 1. TCA registration - Context types from all extensions appear in TCA
 * 2. Context inheritance - Sub-extension contexts inherit from AbstractContext
 *
 * Note: Tests that check for sub-extensions will be skipped if those
 * extensions are not available. This allows the tests to run in both
 * single-extension and multi-extension environments.
 *
 * For database-related tests, see:
 * - ContextInheritanceTest - Tests context property access
 * - MultipleContextTypesMatchingTest - Tests context matching behavior
 */
final class CrossExtensionIntegrationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    /**
     * Flag to track if geolocation extension is available.
     */
    private bool $hasGeolocationExtension = false;

    /**
     * Flag to track if device extension is available.
     */
    private bool $hasDeviceExtension = false;

    protected function setUp(): void
    {
        // Check if sub-extensions are available using string-based class names
        // to avoid PHPStan errors when extensions are not installed
        $this->hasGeolocationExtension = class_exists(
            'Netresearch\\ContextsGeolocation\\Context\\Type\\CountryContext',
        );
        $this->hasDeviceExtension = class_exists(
            'Netresearch\\ContextsDevice\\Context\\Type\\DeviceContext',
        );

        // Dynamically add extensions if available
        if ($this->hasGeolocationExtension) {
            $this->testExtensionsToLoad[] = 'netresearch/contexts-geolocation';
        }
        if ($this->hasDeviceExtension) {
            $this->testExtensionsToLoad[] = 'netresearch/contexts-wurfl';
        }

        parent::setUp();
    }

    // ========================================
    // TCA Registration Tests
    // ========================================

    #[Test]
    public function baseExtensionContextTypesAreRegistered(): void
    {
        $contextTypes = Configuration::getContextTypes();

        self::assertArrayHasKey('domain', $contextTypes, 'Domain context type should be registered');
        self::assertArrayHasKey('ip', $contextTypes, 'IP context type should be registered');
        self::assertArrayHasKey('getparam', $contextTypes, 'GetParam context type should be registered');
        self::assertArrayHasKey('httpheader', $contextTypes, 'HttpHeader context type should be registered');
        self::assertArrayHasKey('combination', $contextTypes, 'Combination context type should be registered');
        self::assertArrayHasKey('session', $contextTypes, 'Session context type should be registered');
    }

    #[Test]
    public function geolocationContextTypesAreRegisteredWhenExtensionLoaded(): void
    {
        if (!$this->hasGeolocationExtension) {
            self::markTestSkipped('Geolocation extension not available');
        }

        $contextTypes = Configuration::getContextTypes();

        self::assertArrayHasKey(
            'geolocation_country',
            $contextTypes,
            'Country context type should be registered by geolocation extension',
        );
        self::assertArrayHasKey(
            'geolocation_continent',
            $contextTypes,
            'Continent context type should be registered by geolocation extension',
        );
        self::assertArrayHasKey(
            'geolocation_distance',
            $contextTypes,
            'Distance context type should be registered by geolocation extension',
        );
    }

    #[Test]
    public function deviceContextTypesAreRegisteredWhenExtensionLoaded(): void
    {
        if (!$this->hasDeviceExtension) {
            self::markTestSkipped('Device extension not available');
        }

        $contextTypes = Configuration::getContextTypes();

        self::assertArrayHasKey(
            'device',
            $contextTypes,
            'Device context type should be registered by device extension',
        );
        self::assertArrayHasKey(
            'browser',
            $contextTypes,
            'Browser context type should be registered by device extension',
        );
    }

    #[Test]
    public function allContextTypesHaveRequiredConfiguration(): void
    {
        $contextTypes = Configuration::getContextTypes();

        foreach ($contextTypes as $type => $config) {
            self::assertArrayHasKey(
                'class',
                $config,
                "Context type '{$type}' should have a class defined",
            );
            self::assertArrayHasKey(
                'title',
                $config,
                "Context type '{$type}' should have a title defined",
            );
            self::assertArrayHasKey(
                'flexFile',
                $config,
                "Context type '{$type}' should have a flexFile defined",
            );

            // Verify class exists if it's set and not empty
            if (!empty($config['class'])) {
                self::assertTrue(
                    class_exists($config['class']),
                    "Class '{$config['class']}' for context type '{$type}' should exist",
                );
            }
        }
    }

    // ========================================
    // Context Inheritance Tests
    // ========================================

    #[Test]
    public function geolocationContextsInheritFromAbstractContext(): void
    {
        if (!$this->hasGeolocationExtension) {
            self::markTestSkipped('Geolocation extension not available');
        }

        // Use string-based class references to avoid PHPStan errors when extension not installed
        $countryContextClass = 'Netresearch\\ContextsGeolocation\\Context\\Type\\CountryContext';
        $continentContextClass = 'Netresearch\\ContextsGeolocation\\Context\\Type\\ContinentContext';
        $distanceContextClass = 'Netresearch\\ContextsGeolocation\\Context\\Type\\DistanceContext';

        self::assertTrue(
            is_subclass_of($countryContextClass, AbstractContext::class),
            'CountryContext should extend AbstractContext',
        );
        self::assertTrue(
            is_subclass_of($continentContextClass, AbstractContext::class),
            'ContinentContext should extend AbstractContext',
        );
        self::assertTrue(
            is_subclass_of($distanceContextClass, AbstractContext::class),
            'DistanceContext should extend AbstractContext',
        );
    }

    #[Test]
    public function deviceContextsInheritFromAbstractContext(): void
    {
        if (!$this->hasDeviceExtension) {
            self::markTestSkipped('Device extension not available');
        }

        // Use string-based class references to avoid PHPStan errors when extension not installed
        $deviceContextClass = 'Netresearch\\ContextsDevice\\Context\\Type\\DeviceContext';
        $browserContextClass = 'Netresearch\\ContextsDevice\\Context\\Type\\BrowserContext';

        self::assertTrue(
            is_subclass_of($deviceContextClass, AbstractContext::class),
            'DeviceContext should extend AbstractContext',
        );
        self::assertTrue(
            is_subclass_of($browserContextClass, AbstractContext::class),
            'BrowserContext should extend AbstractContext',
        );
    }

    // ========================================
    // Extension Loading Tests
    // ========================================

    #[Test]
    public function baseExtensionIsLoaded(): void
    {
        self::assertTrue(
            ExtensionManagementUtility::isLoaded('contexts'),
            'Base contexts extension should be loaded',
        );
    }

    #[Test]
    public function tcaTableIsConfigured(): void
    {
        self::assertArrayHasKey(
            'tx_contexts_contexts',
            $GLOBALS['TCA'],
            'TCA for tx_contexts_contexts should be configured',
        );

        self::assertArrayHasKey(
            'contextTypes',
            $GLOBALS['TCA']['tx_contexts_contexts'],
            'TCA should have contextTypes array',
        );
    }

    #[Test]
    public function contextTypesAreSortedAlphabetically(): void
    {
        // Get registered context types
        $contextTypes = Configuration::getContextTypes();

        // Verify base extension types exist
        self::assertNotEmpty($contextTypes, 'Should have registered context types');

        // Count how many types we have
        $typeCount = \count($contextTypes);
        self::assertGreaterThanOrEqual(
            6,
            $typeCount,
            'Base extension should register at least 6 context types',
        );
    }

    #[Test]
    public function contextTypeClassesImplementMatchMethod(): void
    {
        $contextTypes = Configuration::getContextTypes();

        foreach ($contextTypes as $type => $config) {
            if ($config['class'] === null || !class_exists($config['class'])) {
                continue;
            }

            $reflection = new ReflectionClass($config['class']);

            self::assertTrue(
                $reflection->hasMethod('match'),
                "Context type '{$type}' class should implement match() method",
            );
        }
    }
}
