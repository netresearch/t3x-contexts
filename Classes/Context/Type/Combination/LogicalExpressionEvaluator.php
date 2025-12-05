<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type\Combination;

use stdClass;

/**
 * An evaluator that tokenizes, parses and evaluates logical expressions
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class LogicalExpressionEvaluator
{
    /**
     * Identifier for any unknown token
     * @var int
     */
    public const T_UNKNOWN = 0;

    /**
     * Identifier for && token
     * @var int
     */
    public const T_AND = 1;

    /**
     * Identifier for || token
     * @var int
     */
    public const T_OR = 2;

    /**
     * Identifier for >< token
     * @var int
     */
    public const T_XOR = 3;

    /**
     * Identifier for ! token
     * @var int
     */
    public const T_NEGATE = 4;

    /**
     * Identifier for ( token
     * @var int
     */
    public const T_PL = 5;

    /**
     * Identifier for ) token
     * @var int
     */
    public const T_PR = 6;

    /**
     * Identifier for variable tokens
     * @var int
     */
    public const T_VAR = 7;

    /**
     * Identifier for end token
     * @var int
     */
    public const T_END = 8;

    /**
     * Token identifier to token map
     */
    protected static array $operatorMap = [
        self::T_AND => '&&',
        self::T_OR => '||',
        self::T_XOR => '><',
        self::T_NEGATE => '!',
        self::T_PL => '(',
        self::T_PR => ')',
    ];

    /**
     * Scope container - required to share the scope array
     * between all current scope instances
     *
     */
    protected ?stdClass $scopeContainer = null;

    /**
     * Token array: Can contain
     * * integers (operators)
     * * arrays (variables)
     * * self (scopes)
     *
     */
    protected array $tokens = [];

    /**
     * Parent scope - set by @see pushScope() (that's NOT the same as getScope())
     *
     */
    protected ?LogicalExpressionEvaluator $parentScope = null;

    /**
     * Set when a negation token was handled and
     * factored in when the next token gets pushed
     *
     */
    protected bool $nextTokenNegated = false;

    /**
     * Set on scopes by pushToken() when they are negated
     */
    protected bool $negated = false;

    /**
     * After precedence shifting the operator tokens
     * are extracted and set to this variable
     *
     */
    protected int $operator = self::T_UNKNOWN;

    /**
     * Shortcut to instantiate, tokenize, parse and evaluate
     *
     *
     * @throws LogicalExpressionEvaluatorException
     */
    public static function run(string $expression, array $values): bool
    {
        $evaluator = new self();
        $evaluator->parse($evaluator->tokenize($expression));
        return $evaluator->evaluate($values);
    }

    /**
     * Tokenize an expression. Returns an array of tokens which
     * can be either integer when they are operators or array
     * when they are variable or unknown tokens (the identifier
     * is then in key 0 and the token in key 1)
     *
     *
     */
    public function tokenize(string $expression): array
    {
        $expression = preg_replace(
            [
                '/([^\w])or([^\w])/i',
                '/([^\w])and([^\w])/i',
                '/([^\w])xor([^\w])/i',
            ],
            [
                '$1||$2',
                '$1&&$2',
                '$1><$2',
            ],
            $expression,
        );

        if ($expression === null) {
            return [];
        }

        $pattern = '/[^\w\-_]/';

        /** @var string[][] $operators */
        $operators = [];

        preg_match_all($pattern, $expression . ' ', $operators, \PREG_OFFSET_CAPTURE);

        $nextPosition = 0;
        $tokens = [];
        $lastOperator = '';
        $operatorMap = array_flip(self::$operatorMap);
        foreach ($operators[0] as $operator) {
            if ($lastOperator !== '') {
                if (\array_key_exists($lastOperator . $operator[0], $operatorMap)) {
                    $tokens[] = $operatorMap[$lastOperator . $operator[0]];
                } else {
                    $tokens[] = [
                        self::T_UNKNOWN,
                        $lastOperator,
                    ];
                }
                $operator[0] = '';
            }

            if (($operator[1] !== 0) && (($operator[1] - $nextPosition) !== 0)) {
                $tokens[] = [
                    self::T_VAR,
                    strtolower(substr($expression, $nextPosition, $operator[1] - $nextPosition)),
                ];
            }

            $nextPosition = $operator[1] + 1;

            if (\array_key_exists($operator[0], $operatorMap)) {
                $tokens[] = $operatorMap[$operator[0]];
                $lastOperator = '';
            } elseif (trim($operator[0]) !== '') {
                $lastOperator = $operator[0];
            } else {
                $lastOperator = '';
            }
        }

        $tokens[] = self::T_END;

        return $tokens;
    }

    /**
     * Parse the tokens
     *
     *
     * @throws LogicalExpressionEvaluatorException
     */
    public function parse(array $tokens): void
    {
        $this->scopeContainer = new stdClass();
        $this->scopeContainer->scopes = [];
        $this->scopeContainer->keys = [];
        $this->pushScope();

        $this->tokens = [
            $this->getScope(),
        ];

        foreach ($tokens as $token) {
            $this->getScope()->handleToken($token);
        }

        if ($this->scopeContainer !== null) {
            foreach ($this->scopeContainer->scopes as $scope) {
                $scope->precedenceShiftTokens([
                    self::T_AND,
                    self::T_XOR,
                    self::T_OR,
                ]);
            }
        }
    }

    /**
     * Evalute the tokenized and parsed expression with the
     * provided values
     *
     * @throws LogicalExpressionEvaluatorException
     */
    public function evaluate(array $values): bool
    {
        // default if no operator isset
        $value = false;
        $lastValue = null;

        foreach ($this->tokens as $i => $token) {
            if ($token instanceof self) {
                $value = $token->evaluate($values);
            } else {
                if (!\array_key_exists($token[1], $values)) {
                    $values[$token[1]] = true;
                }
                $value = $values[$token[1]];
                if ($value === 'disabled') {
                    // context is disabled, so treat it as matching
                    $value = true;
                } elseif (\array_key_exists(2, $token)) {
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
                        throw new LogicalExpressionEvaluatorException(
                            'Can\'t evaluate more than two items by xor',
                            3563712913,
                        );
                    }
                    if ($i === 0) {
                        $lastValue = $value;
                    } else {
                        $value = $value !== $lastValue;
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
     *
     */
    public function rebuild(bool $unshifted = false): string
    {
        $parts = [];
        foreach ($this->tokens as $token) {
            if ($token instanceof self) {
                $str = $token->rebuild($unshifted);
                if ($token->parentScope !== null) {
                    $str = '(' . $str . ')';
                }
                if ($token->negated) {
                    $str = '!' . $str;
                }
            } elseif ($unshifted && \is_int($token)) {
                $str = \array_key_exists($token, self::$operatorMap) ? self::$operatorMap[$token] : '?';
                $str = ' ' . $str . ' ';
            } else {
                $str = $token[1];
                if (\array_key_exists(2, $token)) {
                    $str = '!' . $str;
                }
            }
            $parts[] = $str;
        }

        $operator = \array_key_exists($this->operator, self::$operatorMap) ? self::$operatorMap[$this->operator] : '?';
        $operator = ' ' . $operator . ' ';

        return implode($operator, $parts);
    }

    /**
     * Instantiate and push a scope to the scope stack
     * and increase the pointer stack
     *
     */
    protected function pushScope(): void
    {
        if ($this->scopeContainer === null) {
            return;
        }

        $scope = new self();
        $scope->parentScope = $this;
        $scope->scopeContainer = $this->scopeContainer;

        $this->scopeContainer->scopes[] = $scope;
        $this->scopeContainer->keys[] = array_key_last($this->scopeContainer->scopes);
    }

    /**
     * Pop the current scope (key)
     *
     */
    protected function popScope(): void
    {
        if ($this->scopeContainer !== null && !empty($this->scopeContainer->keys)) {
            array_pop($this->scopeContainer->keys);
        }
    }

    /**
     * Get the current scope (not to confuse with parent scope)
     *
     */
    protected function getScope(): LogicalExpressionEvaluator
    {
        if ($this->scopeContainer === null || empty($this->scopeContainer->keys)) {
            return $this;
        }

        return $this->scopeContainer->scopes[end($this->scopeContainer->keys)];
    }

    /**
     * Handle a token: Creates scopes for each expression in parentheses and
     * does some syntax checks
     *
     * @param int|array $token
     * @throws LogicalExpressionEvaluatorException
     */
    protected function handleToken($token): void
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

                if (($scope->parentScope !== null) && ($scope->parentScope->parentScope === null)) {
                    throw new LogicalExpressionEvaluatorException(
                        'Found not opened closing parentheses',
                        6590670096,
                    );
                }

                if (\is_int(end($this->tokens))) {
                    throw new LogicalExpressionEvaluatorException(
                        'Unexpected )',
                        9855977305,
                    );
                }

                if ($this->parentScope !== null) {
                    $this->parentScope->pushToken($scope);
                }

                break;

            case self::T_AND:
            case self::T_OR:
            case self::T_XOR:
                if ((\count($this->tokens) === 0) || \is_int(end($this->tokens))) {
                    throw new LogicalExpressionEvaluatorException(
                        'Unexpected Operator',
                        1651471071,
                    );
                }
                $this->pushToken($token);
                break;

            case self::T_END:
                if (\is_int(end($this->tokens))) {
                    throw new LogicalExpressionEvaluatorException(
                        'Unexpected end',
                        8606073221,
                    );
                }

                if (
                    ($this->getScope()->parentScope !== null)
                    && ($this->getScope()->parentScope->parentScope !== null)
                ) {
                    throw new LogicalExpressionEvaluatorException(
                        'Missing closing parentheses',
                        3681365195,
                    );
                }

                break;

            default:
                if (\is_array($token)) {
                    if ($token[0] === self::T_VAR) {
                        if ((\count($this->tokens) > 0) && !\is_int(end($this->tokens))) {
                            throw new LogicalExpressionEvaluatorException(
                                'Unexpected variable',
                                7706253055,
                            );
                        }
                        $this->pushToken($token);
                    } else {
                        throw new LogicalExpressionEvaluatorException(
                            'Unexpected "' . $token[1] . '"',
                            1064266885,
                        );
                    }
                }
        }
    }

    /**
     * Add a token to the current scope tokens
     *
     * @param int|array|LogicalExpressionEvaluator $token
     * @throws LogicalExpressionEvaluatorException
     */
    protected function pushToken($token): void
    {
        if ($this->nextTokenNegated) {
            if (\is_array($token)) {
                $token[2] = true;
            } elseif ($token instanceof self) {
                $token->negated = true;
            } else {
                throw new LogicalExpressionEvaluatorException(
                    '! can\'t preceded operators',
                    4778280443,
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
     *
     * @throws LogicalExpressionEvaluatorException
     */
    protected function precedenceShiftTokens(array $precedences): void
    {
        $operator = array_shift($precedences);

        if (!$operator) {
            return;
        }

        if (\in_array($operator, $this->tokens, true)) {
            $this->operator = $operator;
            $tokens = $this->tokens;
            $this->tokens = [
                $scope = new self(),
            ];

            foreach ($tokens as $token) {
                if ($token === $this->operator) {
                    $scope = new self();
                    $this->tokens[] = $scope;
                } else {
                    $scope->pushToken($token);
                }
            }

            foreach ($this->tokens as $token) {
                $token->precedenceShiftTokens($precedences);
            }
        }

        $this->precedenceShiftTokens($precedences);
    }
}
