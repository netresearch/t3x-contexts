<?php

namespace Netresearch\Contexts\Context\Type;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;

/**
 * Matches when a logical expression with other contexts evaluates to true.
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class CombinationContext extends AbstractContext
{
    /**
     * @var LogicalExpressionEvaluator
     */
    protected $evaluator;

    /**
     * @var array
     */
    protected $tokens;

    /**
     * Initialize the evaluator, tokenize the expression and create
     * the depencies from the variable tokens.
     *
     * @param array $arContexts the available contexts
     *
     * @return array
     */
    public function getDependencies($arContexts)
    {
        $this->evaluator = new LogicalExpressionEvaluator();
        $this->tokens = $this->evaluator->tokenize($this->getConfValue('field_expression'));
        $dependencies = [];
        foreach ($this->tokens as $token) {
            if (is_array($token)
                && $token[0] === LogicalExpressionEvaluator::T_VAR
            ) {
                foreach ($arContexts as $dependent) {
                    if ($dependent->getAlias() == $token[1]) {
                        $context = $dependent;
                    }
                }

                if (isset($context)) {
                    if (!$context->getDisabled()) {
                        $dependencies[$context->getUid()] = true;
                    } else {
                        $dependencies[$context->getUid()] = false;
                    }
                }
                // Missing contexts will be detected later in match method
            }
        }

        return $dependencies;
    }

    /**
     * Create an array with uids and aliases as keys and the
     * matching result as values and let the evaluator evaluate
     * the parsed expression by those values.
     *
     * @param array $arDependencies
     *
     * @return bool
     */
    public function match(array $arDependencies = [])
    {
        $this->evaluator->parse($this->tokens);
        $values = [];
        foreach ($arDependencies as $dependency) {
            if ($dependency->context->getAlias()) {
                $values[$dependency->context->getAlias()] = $dependency->matched;
            }
            $values[$dependency->context->getUid()] = $dependency->matched;
        }
        // TODO: Should we try/catch parsing and evaluation?
        return $this->invert($this->evaluator->evaluate($values));
    }
}
