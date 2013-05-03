<?php
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

/**
 * Matches when a logical expression with other contexts evaluates to true
 *
 * @package Contexts
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_Combination extends Tx_Contexts_Context_Abstract
{
    protected $evaluator;

    protected $tokens;



    /**
     * Initialize the evaluator, tokenize the expression and create
     * the depencies from the variable tokens
     *
     * @return array
     */
    public function getDependencies()
    {
        $this->evaluator = new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator();
        $this->tokens = $this->evaluator->tokenize($this->getConfValue('field_expression'));
        $dependencies = array();
        foreach ($this->tokens as $token) {
            if (is_array($token)
                && $token[0] === Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::T_VAR
            ) {
                $context = $this->findInContainer($token);

                if ($context) {
                    $dependencies[$context->getUid()] = true;
                }
                // Missing contexts will be detected later in match method
            }
        }
        return $dependencies;
    }

    /**
     * Find context by token
     *
     * @param array $arToken Token array with 0->type and 1->value
     *
     * @return Tx_Contexts_Context_Abstract Context object or NULL if not found
     *
     * @see Tx_Contexts_Context_Container::find
     */
    protected function findInContainer($arToken)
    {
        $container = Tx_Contexts_Context_Container::get();
        return $container->find($arToken[1]);
    }

    /**
     * Create an array with uids and aliases as keys and the
     * matching result as values and let the evaluator evaluate
     * the parsed expression by those values.
     *
     * @param array $arDependencies
     * @return boolean
     */
    public function match(array $arDependencies = array())
    {
        $this->evaluator->parse($this->tokens);
        $values = array();
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
?>
