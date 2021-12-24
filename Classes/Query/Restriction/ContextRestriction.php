<?php
namespace Netresearch\Contexts\Query\Restriction;

/***************************************************************
 *  Copyright notice - MIT License (MIT)
 *
 *  (c) 2018 b:dreizehn GmbH,
 * 		Achim Fritz <achim.fritz@b13.de>
 *  All rights reserved
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 ***************************************************************/

use TYPO3\CMS\Core\Database\Query\Restriction\EnforceableQueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\Container;

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
                        )
                    ];
                    $disableConstraints = [];
                    foreach (Container::get() as $context) {
                        $enableConstraints[] = $expressionBuilder->inSet(
                            $flatColumns[1],
                            (int)$context->getUid()
                        );
                        $disableConstraints[] = 'NOT ' . $expressionBuilder->inSet(
                                $flatColumns[0],
                                (int)$context->getUid()
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
    protected function isEnvironmentInFrontendMode()
    {
        return (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') ?: false;
    }
}
