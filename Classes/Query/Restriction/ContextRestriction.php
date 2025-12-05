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
                foreach (Configuration::getEnableSettings($table) as $setting) {
                    $flatColumns = Configuration::getFlatColumns($table, $setting);

                    if (\count($flatColumns) === 0) {
                        continue;
                    }

                    $enableConstraints = [
                        $expressionBuilder->isNull($flatColumns[1]),
                        $expressionBuilder->eq(
                            $flatColumns[1],
                            $expressionBuilder->literal(''),
                        ),
                    ];
                    $disableConstraints = [];

                    /** @var AbstractContext $context */
                    foreach (Container::get() as $context) {
                        $enableConstraints[] = $expressionBuilder->inSet(
                            $flatColumns[1],
                            (string) $context->getUid(),
                        );
                        $disableConstraints[] = 'NOT ' . $expressionBuilder->inSet(
                            $flatColumns[0],
                            (string) $context->getUid(),
                        );
                    }

                    $constraints[] = $expressionBuilder->or(
                        ...$enableConstraints,
                    );

                    if (\count($disableConstraints) > 0) {
                        $constraints[] = $expressionBuilder->or(
                            $expressionBuilder->isNull($flatColumns[0]),
                            $expressionBuilder->eq(
                                $flatColumns[0],
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
