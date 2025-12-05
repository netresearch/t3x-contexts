<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\ExpressionLanguage\FunctionsProvider;

use Netresearch\Contexts\Api\ContextMatcher;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * Class ContextConditionProvider
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ContextFunctionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions(): array
    {
        return [
            $this->getContextMatch(),
        ];
    }

    /**
     */
    protected function getContextMatch(): ExpressionFunction
    {
        return new ExpressionFunction(
            'contextMatch',
            static function (): void {
                // Not implemented, we only use the evaluators
            },
            fn ($arguments, $strContext) => ContextMatcher::getInstance()->matches($strContext),
        );
    }
}
