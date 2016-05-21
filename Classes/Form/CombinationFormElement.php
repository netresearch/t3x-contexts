<?php
namespace Bmack\Contexts\Form;

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

use Bmack\Contexts\Context\Container;
use Bmack\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use TYPO3\CMS\Backend\Form\FormEngine;

/**
 * Provides methods used in the backend by flexforms.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @author     Marian Pollzien <marian.pollzien@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts
 */
class CombinationFormElement
{
    /**
     * Display a textarea with validation for the entered aliases and expressions
     *
     * @param array          $arFieldInfo Information about the current input field
     * @param FormEngine     $formEngineObject    Form rendering library object
     * @return string HTML code
     */
    public function render($arFieldInfo, FormEngine $formEngineObject)
    {
        $text = $formEngineObject->getSingleField_typeText(
            $arFieldInfo['table'], $arFieldInfo['field'],
            $arFieldInfo['row'], $arFieldInfo
        );
        $evaluator = new LogicalExpressionEvaluator();
        $arTokens = $evaluator->tokenize($arFieldInfo['itemFormElValue']);

        $arNotFound = array();
        $arUnknownTokens = array();
        foreach ($arTokens as $token) {
            if (is_array($token)
                && $token[0] === LogicalExpressionEvaluator::T_VAR
            ) {
                $contexts = Container::get()->initAll();
                $bFound = false;
                foreach ($contexts as $context) {
                    if ($context->getAlias() == $token[1]) {
                        $bFound = true;
                    }
                }

                if (!$bFound) {
                    $arNotFound[] = $token[1];
                }
            } elseif (is_array($token)
                && $token[0] === LogicalExpressionEvaluator::T_UNKNOWN
            ) {
                $arUnknownTokens[] = $token[1];
            }
        }

        if (!$arNotFound && !$arUnknownTokens) {
            return $text;
        }

        $html = <<<HTM
$text<br />
<div class="typo3-message message-error">
    <div class="message-body">
HTM;
        if ($arNotFound) {
            $strNotFound = implode(', ', $arNotFound);
            $html .= <<<HTM
<div>
    {$GLOBALS['LANG']->sL('LLL:EXT:contexts/Resources/Private/Language'
        .'/flexform.xml:aliasesNotFound')}: $strNotFound
</div>
HTM;
        }

        if ($arUnknownTokens) {
            $strUnknownTokens = implode(', ', $arUnknownTokens);
            $html .= <<<HTM
<div>
    {$GLOBALS['LANG']->sL('LLL:EXT:contexts/Resources/Private/Language'
        .'/flexform.xml:unknownTokensFound')}: $strUnknownTokens
</div>
HTM;
        }

        $html .= <<<HTM
    </div>
</div>
HTM;

        return $html;
    }
}