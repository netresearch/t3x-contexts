<?php
namespace Netresearch\Contexts\ExpressionLanguage\FunctionsProvider;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;


/**
 * Class ContextConditionProvider
 * @package Netresearch\Contexts\ExpressionLanguage
 */
class ContextFunctionsProvider implements ExpressionFunctionProviderInterface
{

    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return [
            $this->getContextMatch()
        ];
    }

    /**
     * @return ExpressionFunction
     */
    protected function getContextMatch()
    {
        return new ExpressionFunction('contextMatch', function () {
            // Not implemented, we only use the evaluator
        }, function ($arguments, $strContext) {
            return \Netresearch\Contexts\Api\ContextMatcher::getInstance()
                ->matches($strContext);
        });
    }

}
