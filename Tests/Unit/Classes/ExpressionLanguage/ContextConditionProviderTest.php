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

namespace Netresearch\Contexts\Tests\Unit\ExpressionLanguage;

use Netresearch\Contexts\ExpressionLanguage\ContextConditionProvider;
use Netresearch\Contexts\ExpressionLanguage\FunctionsProvider\ContextFunctionsProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\ExpressionLanguage\AbstractProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for ContextConditionProvider.
 *
 * ContextConditionProvider is a TYPO3 expression language provider that registers
 * the ContextFunctionsProvider, making contextMatch() available in TypoScript
 * conditions such as [contextMatch("mobile")].
 *
 * It extends TYPO3's AbstractProvider and populates the
 * $expressionLanguageProviders list during construction.
 */
final class ContextConditionProviderTest extends UnitTestCase
{
    // ========================================
    // Inheritance and interface
    // ========================================

    #[Test]
    public function extendsAbstractProvider(): void
    {
        $provider = new ContextConditionProvider();

        self::assertInstanceOf(AbstractProvider::class, $provider);
    }

    // ========================================
    // Constructor — provider registration
    // ========================================

    #[Test]
    public function constructorRegistersContextFunctionsProvider(): void
    {
        $provider = new ContextConditionProvider();

        $providers = $provider->getExpressionLanguageProviders();

        self::assertContains(ContextFunctionsProvider::class, $providers);
    }

    #[Test]
    public function getExpressionLanguageProvidersReturnsNonEmptyArray(): void
    {
        $provider = new ContextConditionProvider();

        $providers = $provider->getExpressionLanguageProviders();

        self::assertNotEmpty($providers);
    }

    #[Test]
    public function getExpressionLanguageProvidersContainsExactlyOneEntry(): void
    {
        $provider = new ContextConditionProvider();

        $providers = $provider->getExpressionLanguageProviders();

        self::assertCount(1, $providers);
    }

    #[Test]
    public function getExpressionLanguageProvidersReturnsListWithContextFunctionsProviderAsFirstEntry(): void
    {
        $provider = new ContextConditionProvider();

        $providers = $provider->getExpressionLanguageProviders();

        self::assertSame(ContextFunctionsProvider::class, $providers[0]);
    }

    // ========================================
    // Variables — none registered
    // ========================================

    #[Test]
    public function getExpressionLanguageVariablesReturnsEmptyArray(): void
    {
        $provider = new ContextConditionProvider();

        $variables = $provider->getExpressionLanguageVariables();

        self::assertSame([], $variables);
    }

    // ========================================
    // Multiple instantiations are independent
    // ========================================

    #[Test]
    public function eachInstanceHasItsOwnProviderList(): void
    {
        $providerA = new ContextConditionProvider();
        $providerB = new ContextConditionProvider();

        self::assertSame(
            $providerA->getExpressionLanguageProviders(),
            $providerB->getExpressionLanguageProviders(),
        );
    }

    #[Test]
    public function registeredProviderClassIsInstantiable(): void
    {
        $provider = new ContextConditionProvider();
        $providers = $provider->getExpressionLanguageProviders();

        foreach ($providers as $providerClass) {
            self::assertTrue(
                class_exists($providerClass),
                \sprintf('Provider class "%s" must exist and be autoloadable.', $providerClass),
            );
        }
    }
}
