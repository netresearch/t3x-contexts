<?php

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
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use function count;
use function defined;

/**
 * Class ContextRestriction
 * @package Netresearch\Contexts\Query\Restrictio
 */
class ContextRestriction implements QueryRestrictionInterface, EnforceableQueryRestrictionInterface
{
    /**
     * @return bool
     */
    public function isEnforced(): bool
    {
        return true;
    }

    /**
     * @param array $queriedTables
     * @param ExpressionBuilder $expressionBuilder
     * @return CompositeExpression
     */
    public function buildExpression(array $queriedTables, ExpressionBuilder $expressionBuilder): CompositeExpression
    {
        $constraints = [];
        if ($this->isEnvironmentInFrontendMode()) {
            foreach ($queriedTables as $table) {
                foreach (Configuration::getEnableSettings($table) as $setting) {
                    $flatColumns = Configuration::getFlatColumns($table, $setting);
                    if (!$flatColumns) {
                        continue;
                    }
                    $enableConstraints = [
                        $expressionBuilder->isNull($flatColumns[1]),
                        $expressionBuilder->eq(
                            $flatColumns[1],
                            $expressionBuilder->literal('')
                        ),
                    ];
                    $disableConstraints = [];

                    /** @var AbstractContext $context */
                    foreach (Container::get() as $context) {
                        $enableConstraints[] = $expressionBuilder->inSet(
                            $flatColumns[1],
                            (string) $context->getUid()
                        );
                        $disableConstraints[] = 'NOT ' . $expressionBuilder->inSet(
                                $flatColumns[0],
                                (string) $context->getUid()
                            );
                    }
                    $constraints[] = $expressionBuilder->orX(
                        ...$enableConstraints
                    );
                    if (count($disableConstraints)) {
                        $constraints[] = $expressionBuilder->orX(
                            $expressionBuilder->isNull($flatColumns[0]),
                            $expressionBuilder->eq(
                                $flatColumns[0],
                                $expressionBuilder->literal('')
                            ),
                            $expressionBuilder->andX(...$disableConstraints)
                        );
                    }
                }
            }
        }
        return $expressionBuilder->andX(...$constraints);
    }

    /**
     * @return bool
     */
    protected function isEnvironmentInFrontendMode(): bool
    {
        return defined('TYPO3_MODE') && TYPO3_MODE === 'FE';
    }
}