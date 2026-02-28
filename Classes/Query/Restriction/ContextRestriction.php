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

namespace Netresearch\Contexts\Query\Restriction;

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Container;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Http\ApplicationType;

/**
 * Class ContextRestriction
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ContextRestriction implements EnforceableQueryRestrictionInterface, QueryRestrictionInterface
{
    /**
     */
    public function isEnforced(): bool
    {
        return true;
    }

    /**
     *
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];

        if ($this->isEnvironmentInFrontendMode()) {
            foreach ($queriedTables as $table) {
                $table = (string) $table;
                foreach (Configuration::getEnableSettings($table) as $setting) {
                    $setting = (string) $setting;
                    $flatColumns = Configuration::getFlatColumns($table, $setting);

                    if (\count($flatColumns) === 0) {
                        continue;
                    }

                    $disableColumn = (string) $flatColumns[0];
                    $enableColumn = (string) $flatColumns[1];

                    $enableConstraints = [
                        $expressionBuilder->isNull($enableColumn),
                        $expressionBuilder->eq(
                            $enableColumn,
                            $expressionBuilder->literal(''),
                        ),
                    ];
                    $disableConstraints = [];

                    /** @var AbstractContext $context */
                    foreach (Container::get() as $context) {
                        $enableConstraints[] = $expressionBuilder->inSet(
                            $enableColumn,
                            (string) $context->getUid(),
                        );
                        $disableConstraints[] = 'NOT ' . $expressionBuilder->inSet(
                            $disableColumn,
                            (string) $context->getUid(),
                        );
                    }

                    $constraints[] = $expressionBuilder->or(
                        ...$enableConstraints,
                    );

                    if (\count($disableConstraints) > 0) {
                        $constraints[] = $expressionBuilder->or(
                            $expressionBuilder->isNull($disableColumn),
                            $expressionBuilder->eq(
                                $disableColumn,
                                $expressionBuilder->literal(''),
                            ),
                            $expressionBuilder->and(...$disableConstraints),
                        );
                    }
                }
            }
        }

        return $expressionBuilder->and(...$constraints);
    }

    /**
     */
    protected function isEnvironmentInFrontendMode(): bool
    {
        return \defined('TYPO3')
            && (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface)
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend();
    }
}
