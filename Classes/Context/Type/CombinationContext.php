<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException;
use function is_array;

/**
 * Matches when a logical expression with other contexts evaluates to true
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class CombinationContext extends AbstractContext
{
    /**
     * @var LogicalExpressionEvaluator
     */
    protected LogicalExpressionEvaluator $evaluator;

    /**
     * @var array
     */
    protected array $tokens;

    /**
     * Initialize the evaluator, tokenize the expression and create
     * the dependencies from the variable tokens
     *
     * @param array $arContexts the available contexts
     *
     * @return array
     */
    public function getDependencies(array $arContexts): array
    {
        $this->evaluator = new LogicalExpressionEvaluator();
        $this->tokens = $this->evaluator->tokenize($this->getConfValue('field_expression'));
        $dependencies = [];
        foreach ($this->tokens as $token) {
            if (is_array($token)
                && $token[0] === LogicalExpressionEvaluator::T_VAR
            ) {
                foreach ($arContexts as $dependent) {
                    if ($dependent->getAlias() === $token[1]) {
                        $context = $dependent;
                    }
                }

                if (isset($context)) {
                    if ($context->getDisabled()) {
                        $dependencies[$context->getUid()] = false;
                    } else {
                        $dependencies[$context->getUid()] = true;
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
     * @throws LogicalExpressionEvaluatorException
     */
    public function match(array $arDependencies = []): bool
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
