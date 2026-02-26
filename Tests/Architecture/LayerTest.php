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

namespace Netresearch\Contexts\Tests\Architecture;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Architecture tests to enforce layer boundaries.
 *
 * @see https://github.com/carlosas/phpat
 */
final class LayerTest
{
    /**
     * Context type classes should only depend on AbstractContext
     * (excludes helper classes like evaluators and exceptions)
     */
    public function testContextTypesExtendAbstract(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\Contexts\Context\Type'))
            ->excluding(
                Selector::classname(LogicalExpressionEvaluator::class),
                Selector::classname(LogicalExpressionEvaluatorException::class),
            )
            ->shouldExtend()
            ->classes(
                Selector::classname(AbstractContext::class),
            )
            ->because('All context types should extend AbstractContext');
    }

    /**
     * Event classes should be final
     */
    public function testEventsAreFinal(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\Contexts\Event'))
            ->shouldBeFinal()
            ->because('Event classes should be final for immutability');
    }

    /**
     * DTO classes should be readonly
     */
    public function testDtosAreReadonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('Netresearch\Contexts\Dto'))
            ->shouldBeReadonly()
            ->because('DTOs should be immutable');
    }
}
