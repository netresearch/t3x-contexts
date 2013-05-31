<?php
/**
 * Part of context extension
 *
 * PHP version 5
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @author     Marian Pollzien <marian.pollzien@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts
 */

/**
 * Provides methods used in the backend by flexforms.
 *
 * @category   TYPO3-Extensions
 * @package    Contexts
 * @author     Marian Pollzien <marian.pollzien@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 * @link       http://github.com/netresearch/contexts
 */
class Tx_Contexts_Backend
{
    /**
     * Display a textarea with validation for the entered aliases and expressions
     *
     * @param array          $arFieldInfo Information about the current input field
     * @param t3lib_tceforms $tceforms    Form rendering library object
     * @return string HTML code
     */
    public function textCombinations($arFieldInfo, t3lib_tceforms $tceforms)
    {
        $text = $tceforms->getSingleField_typeText(
            $arFieldInfo['table'], $arFieldInfo['field'],
            $arFieldInfo['row'], $arFieldInfo
        );
        $evaluator = new Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator();
        $arTokens = $evaluator->tokenize($arFieldInfo['itemFormElValue']);

        $arNotFound = array();
        $arUnknownTokens = array();
        foreach ($arTokens as $token) {
            if (is_array($token)
                && $token[0] === Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::T_VAR
            ) {
                $contexts = Tx_Contexts_Context_Container::get()->initAll();
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
                && $token[0] === Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::T_UNKNOWN
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
?>