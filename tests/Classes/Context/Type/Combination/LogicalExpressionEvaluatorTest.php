<?php

require_once __DIR__ . '../../../../../../Classes/Context/Type/Combination/LogicalExpressionEvaluator.php';
require_once __DIR__ . '../../../../../../Classes/Context/Type/Combination/LogicalExpressionEvaluator/Exception.php';


class Tx_Contexts_Context_Type_LogicalExpressionEvaluatorTest extends PHPUnit_Framework_TestCase
{
    
    /**
     * @dataProvider expressionValueProvider
     */
    public function testRunWithoutException($expression, $values, $expected)
    {   
        self::assertSame(
            $expected,
            Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::run($expression, $values)
        );
        
        
    }
    
    /**
     * @expectedException Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception
     * @expectedExceptionMessage Unexpected end
     */
    public function testRunWithExceptionUnexpectedEnd()
    {   
        
        $strExpression = '(context1 ||';
        $arValues = array('context1' => true);
        Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::run($strExpression, $arValues);
        
        
    }
   
    /**
     * @expectedException Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator_Exception
     * @expectedExceptionMessage Missing closing parentheses
     */
    public function testRunWithExceptionMissingClosingParentheses()
    {   
        
        $strExpression = '(context1';
        $arValues = array('context1' => true);
        Tx_Contexts_Context_Type_Combination_LogicalExpressionEvaluator::run($strExpression, $arValues);
        
        
    }
    
    
    public static function expressionValueProvider()
    {
        return array(
            array('(context1 || context2) ', array('context1'=>true, 'context2'=>false), self::getEval('true || false')),
            array('(context1 || context2) ', array('context1'=>true, 'context2'=>true), self::getEval('true || true')),
            array('(context1 && context2) ', array('context1'=>true, 'context2'=>true), self::getEval('true && true')),
            array('(context1 && context2) ', array('context1'=>true, 'context2'=>false), self::getEval('true && false')),
            array('(context1 xor context2) ', array('context1'=>true, 'context2'=>false), self::getEval('true xor false')),
            array('(context1 xor context2) ', array('context1'=>true, 'context2'=>true), self::getEval('true xor true')),
            array(
                'context1 xor (context2 && context3)',
                array(
                    'context1'=>true,
                    'context2'=>true,
                    'context3'=>false
                ),
                self::getEval('true xor (true && false)'))
        );
    }
    
    protected static function getEval($string)
    {
        return eval("return (".$string.");");
    }
    
}
        
?>


