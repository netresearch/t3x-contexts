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
 * An evaluator that tokenizes, parses and evaluates logical expressions
 *
 * @package Contexts
 * @subpackage Types_Combination
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @license http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator
{
    /**
     * Identifier for any unknown token
     * @var int
     */
    const T_UNKNOWN = 0;

    /**
     * Identifier for && token
     * @var int
     */
    const T_AND = 1;

    /**
     * Identifier for || token
     * @var int
     */
    const T_OR = 2;

    /**
     * Identifier for >< token
     * @var int
     */
    const T_XOR = 3;

    /**
     * Identifier for ! token
     * @var int
     */
    const T_NEGATE = 4;

    /**
     * Identifier for ( token
     * @var int
     */
    const T_PL = 5;

    /**
     * Identifier for ) token
     * @var int
     */
    const T_PR = 6;

    /**
     * Identifier for variable tokens
     * @var int
     */
    const T_VAR = 7;

    /**
     * Identifier for end token
     * @var int
     */
    const T_END = 8;

    /**
     * Scope container - required to share the scope array
     * between all current scope instances
     * @var stdClass
     */
    protected $scopeContainer;

    /**
     * Token array: Can contain
     * * integers (operators)
     * * arrays (variables)
     * * self (scopes)
     *
     * @var array
     */
    protected $tokens = array();

    /**
     * Parent scope - set by @see pushScope()
     * (thats NOT the same as getScope())
     * @var Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator
     */
    protected $parentScope;

    /**
     * Set when an negation token was handled and
     * factored in when the next token gets pushed
     * @var boolean
     */
    protected $nextTokenNegated = false;

    /**
     * Set on scopes by pushToken() when they are negated
     * @var unknown_type
     */
    protected $negated = false;

    /**
     * After precedence shifting the operator tokens
     * are extracted and set to this variable
     * @var int
     */
    protected $operator;

    /**
     * Token identifier to token map
     * @var array
     */
    protected static $operatorMap = array(
        self::T_AND => '&&',
        self::T_OR => '||',
        self::T_XOR => '><',
        self::T_NEGATE => '!',
        self::T_PL => '(',
        self::T_PR => ')'
    );

    /**
     * Shortcut to instantiate, tokenize, parse and evaluate
     *
     * @param string $expression
     * @param array $values
     * @return boolean
     */
    public static function run($expression, $values)
    {
        $evaluator = new self();
        $evaluator->parse($evaluator->tokenize($expression));
        return $evaluator->evaluate($values);
    }

    /**
     * Tokenize an expression. Returns an array of tokens which
     * can be either integer when they are operators or array
     * when they are variable or unkwown tokens (the identifier
     * is then in key 0 and the token in key 1)
     *
     * @param string $expression
     * @return array
     */
    public function tokenize($expression)
    {
        $expression = preg_replace(
            array(
                '/([^\w])or([^\w])/i',
                '/([^\w])and([^\w])/i',
                '/([^\w])xor([^\w])/i',
            ),
            array(
                '$1||$2',
                '$1&&$2',
                '$1><$2',
            ),
            $expression
        );
        $pattern = '/[^\w-_]/';
        preg_match_all($pattern, $expression . ' ', $operators, PREG_OFFSET_CAPTURE);
        $nextPosition = 0;
        $tokens = array();
        $lastOperator = '';
        $operatorMap = array_flip(self::$operatorMap);
        foreach ($operators[0] as $operator) {
            if ($lastOperator) {
                if (array_key_exists($lastOperator . $operator[0], $operatorMap)) {
                    $tokens[] = $operatorMap[$lastOperator . $operator[0]];
                } else {
                    $tokens[] = array(
                        self::T_UNKNOWN,
                        $lastOperator
                    );
                }
                $operator[0] = '';
            }

            if ($operator[1] && $operator[1] - $nextPosition) {
                $tokens[] = array(
                    self::T_VAR,
                    strtolower(substr($expression, $nextPosition, $operator[1] - $nextPosition))
                );
            }

            $nextPosition = $operator[1] + 1;

            if (array_key_exists($operator[0], $operatorMap)) {
                $tokens[] = $operatorMap[$operator[0]];
                $lastOperator = '';
            } elseif (!trim($operator[0])) {
                $lastOperator = '';
            } else {
                $lastOperator = $operator[0];
            }
        }
        $tokens[] = self::T_END;

        return $tokens;
    }

    /**
     * Parse the tokens
     *
     * @param array $tokens
     * @return void
     */
    public function parse($tokens)
    {
        $this->scopeContainer = new stdClass();
        $this->scopeContainer->scopes = array();
        $this->scopeContainer->keys = array();
        $this->pushScope();
        $this->tokens = array($this->getScope());
        foreach ($tokens as $token) {
            $this->getScope()->handleToken($token);
        }
        foreach ($this->scopeContainer->scopes as $scope) {
            $scope->precedenceShiftTokens(array(self::T_AND, self::T_XOR, self::T_OR));
        }
    }

    /**
     * Instantiate and push a scope to the scope stack
     * and increase the pointer stack
     *
     * @return void
     */
    protected function pushScope()
    {
        $scope = new self();
        $scope->parentScope = $this;
        $scope->scopeContainer = $this->scopeContainer;
        $this->scopeContainer->scopes[] = $scope;
        end($this->scopeContainer->scopes);
        $this->scopeContainer->keys[] = key($this->scopeContainer->scopes);
    }

    /**
     * Pop the current scope (key)
     *
     * @return void
     */
    protected function popScope()
    {
        array_pop($this->scopeContainer->keys);
    }

    /**
     * Get the current scope (not to confuse with parent scope)
     *
     * @return Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator
     */
    protected function getScope()
    {
        return $this->scopeContainer->scopes[end($this->scopeContainer->keys)];
    }

    /**
     * Handle a token: Creates scopes for each expression in parenthesis and
     * does some syntax checks
     *
     * @param int|array $token
     * @throws Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception
     * @return void
     */
    protected function handleToken($token)
    {
        switch ($token) {
            case self::T_NEGATE:
                $this->nextTokenNegated = !$this->nextTokenNegated;
                break;
            case self::T_PL:
                $this->pushScope();
                break;
            case self::T_PR:
                $scope = $this->getScope();
                $this->popScope();
                if (!$scope->parentScope->parentScope) {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Found not opened closing parentheses'
                    );
                }
                if (is_int(end($this->tokens))) {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Unexpected )'
                    );
                }
                $this->parentScope->pushToken($scope);
                break;
            case self::T_AND:
            case self::T_OR:
            case self::T_XOR:
                if (!$this->tokens || is_int(end($this->tokens))) {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Unexpected Operator'
                    );
                }
                $this->pushToken($token);
                break;
            case self::T_END:
                if (is_int(end($this->tokens))) {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Unexpected end'
                    );
                }

                if ($this->getScope()->parentScope->parentScope) {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Missing closing parentheses'
                    );
                }

                break;
            default:
                if ($token[0] == self::T_VAR) {
                    if ($this->tokens && !is_int(end($this->tokens))) {
                        throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                            'Unexpected variable'
                        );
                    }
                    $this->pushToken($token);
                } else {
                    throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                        'Unexpected "' . $token[1] . '"'
                    );
                }
        }
    }

    /**
     * Add a token to the current scope tokens
     *
     * @param int|array|Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator $token
     * @throws Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception
     * @return void
     */
    protected function pushToken($token)
    {
        if ($this->nextTokenNegated) {
            if (is_array($token)) {
                $token[2] = true;
            } elseif ($token instanceof self) {
                $token->negated = true;
            } else {
                throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                    '! can\'t preceed operators'
                );
            }
            $this->nextTokenNegated = false;
        }
        $this->tokens[] = $token;
    }

    /**
     * Scans the current tokens for operators by the order they are
     * in $precedences and creates scopes for each expression next to
     * those operators
     *
     * @param array $precedences
     * @return void
     */
    protected function precedenceShiftTokens($precedences)
    {
        $operator = array_shift($precedences);
        if (!$operator) {
            return;
        }
        if (in_array($operator, $this->tokens, true)) {
            $this->operator = $operator;
            $tokens = $this->tokens;
            $this->tokens = array(
                $scope = new self()
            );
            foreach ($tokens as $token) {
                if ($token === $this->operator) {
                    $this->tokens[] = $scope = new self();
                } else {
                    $scope->pushToken($token);
                }
            }
            foreach ($this->tokens as $scope) {
                $scope->precedenceShiftTokens($precedences);
            }
        }
        $this->precedenceShiftTokens($precedences);
    }

    /**
     * Evalute the tokenized and parsed expression with the
     * provided values
     *
     * @param array $values
     * @throws Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception
     * @return boolean
     */
    public function evaluate(array $values)
    {
        // default if no operator isset
        $value = false;

        foreach ($this->tokens as $i => $token) {
            if ($token instanceof self) {
                $value = $token->evaluate($values);
            } else {
                if (!array_key_exists($token[1], $values)) {
                    $values[$token[1]] = true;
                }
                $value = $values[$token[1]];
                if ($value === 'disabled') {
                    // context is disabled, so treat it as matching
                    $value = true;
                } elseif (array_key_exists(2, $token)) {
                    $value = !$value;
                }
            }
            switch ($this->operator) {
                case self::T_AND:
                    if (!$value) {
                        break 2;
                    }
                    break;
                case self::T_OR:
                    if ($value) {
                        break 2;
                    }
                    break;
                case self::T_XOR:
                    if ($i > 1) {
                        throw new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception(
                            'Can\'t evaluate more than two items by xor'
                        );
                    }
                    if ($i == 0) {
                        $lastValue = $value;
                    } else {
                        $value = $value != $lastValue;
                    }
                    break;
                default:
                    break 2;
            }
        }
        return $this->negated ? !$value : $value;
    }

    /**
     * Recreate the expression from the parsed tokens mainly for
     * debugging purpose
     * (set $unshifted to true when the tokens were not yet
     * precedence shifted)
     *
     * @param boolean $unshifted
     * @return string
     */
    public function rebuild($unshifted = false)
    {
        $parts = array();
        foreach ($this->tokens as $i => $token) {
            if ($token instanceof self) {
                $str = $token->rebuild($unshifted);
                if ($token->parentScope) {
                    $str = '(' . $str . ')';
                }
                if ($token->negated) {
                    $str = '!' . $str;
                }
            } else {
                if ($unshifted && is_int($token)) {
                    $str = array_key_exists($token, self::$operatorMap) ? self::$operatorMap[$token] : '?';
                    $str = ' ' . $str . ' ';
                } else {
                    $str = $token[1];
                    if (array_key_exists(2, $token)) {
                        $str = '!' . $str;
                    }
                }
            }
            $parts[] = $str;
        }

        $operator = array_key_exists($this->operator, self::$operatorMap) ? self::$operatorMap[$this->operator] : '?';
        $operator = ' ' . $operator . ' ';

        return implode($operator, $parts);
    }
}
?>
