<?php
namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class LogicalExpressionEvaluatorTest extends UnitTestCase
{

    /**
     * @dataProvider expressionValueProvider
     */
    public function testRunWithoutException($expression, $rebuiltExpression, $values)
    {
        LogicalExpressionEvaluator::run($expression, $values);
        self::assertSame(
            self::getEval($expression, $values),
            LogicalExpressionEvaluator::run($expression, $values)
        );
    }

    /**
     * @dataProvider expressionValueProvider
     */
    public function testRebuild($expression, $rebuiltExpression, $values)
    {
        $evaluator = new LogicalExpressionEvaluator();
        $evaluator->parse($evaluator->tokenize($expression));

        self::assertSame(
            // Rebuilt expression is always wrapped within parenthesis
            // because parser always pushs a scope first
            '('.$rebuiltExpression.')',
            $evaluator->rebuild(),
            'Rebuild must be revised'
        );
    }

    /**
     * @expectedException \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException
     * @expectedExceptionMessage Unexpected end
     */
    public function testRunWithExceptionUnexpectedEnd()
    {

        $strExpression = '(context1 ||';
        $arValues = array('context1' => true);
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    /**
     *
     * @expectedException \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException
     * @expectedExceptionMessage Missing closing parentheses
     */
    public function testRunWithExceptionMissingClosingParentheses()
    {

        $strExpression = '(context1 ';
        $arValues = array('context1' => true);
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

     /**
     *
     * @expectedException \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException
     * @expectedExceptionMessage Unexpected variable
     */
    public function testRunWithExceptionMissingOperator()
    {

        $strExpression = '(context1 context2)';
        $arValues = array('context1' => true);
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    /**
     *
     *
     * @expectedException \Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException
     * @expectedExceptionMessage Can't evaluate more than two items by xor
     */
    public function testRunWithExceptionTwoXor()
    {

        $strExpression = 'context1 xor context2 xor context3';
        $arValues = array('context1' => true, 'context2' => true, 'context3' => true);
        LogicalExpressionEvaluator::run($strExpression, $arValues);
    }

    public function testNot()
    {
        $strExpression = '!a';
        $arValues = array(
            'a' => true,
        );
        $this->assertFalse(
            LogicalExpressionEvaluator::run(
                $strExpression, $arValues
            )
        );
    }

    public function testAndNot()
    {
        $strExpression = 'a && !b';
        $arValues = array(
            'a' => true,
            'b' => true,
        );
        $this->assertFalse(
            LogicalExpressionEvaluator::run(
                $strExpression, $arValues
            )
        );
    }



    /**
     * Provide data for several tests
     * @return array Array of arguments where
     *               1st is the expression
     *               2nd is the expected rebuilt expression
     *               3rd are the values
     */
    public static function expressionValueProvider()
    {
        return array(
            array(
                $e = 'context1 || context2',
                $e,
                array('context1'=>true, 'context2'=>false),
            ),
            array(
                'context1 or context2',
                $e,
                array('context1'=>true, 'context2'=>true)
            ),
            array(
                $e = 'context1 && context2',
                $e,
                array('context1'=>true, 'context2'=>true)
            ),
            array(
                'context1 and context2',
                $e,
                array('context1'=>true, 'context2'=>false)
            ),
            array(
                $e = 'context1 >< context2',
                $e,
                array('context1'=>true, 'context2'=>false)
            ),
            array(
                'context1 xor context2',
                $e,
                array('context1'=>true, 'context2'=>true)
            ),
            array(
                'context1 && !(context2 || !!context3)',
                'context1 && !(context2 || context3)',
                array('context1'=>true, 'context2'=>false, 'context3' => false)
            ),
            array(
                'context1 xor (context2 && !context3)',
                'context1 >< (context2 && !context3)',
                array('context1'=>true, 'context2'=>true, 'context3'=>false)
            ),
            array(
                $e = 'context1-hyphen && context2',
                $e,
                array('context1-hyphen'=>true, 'context2'=>true)
            ),
            array(
                $e = 'context1_underscore && context2',
                $e,
                array('context1_underscore'=>true, 'context2'=>true)
            ),
        );
    }

    protected static function getEval($string, $values)
    {
        $string = str_replace('><', 'xor', $string);
        foreach ($values as $key => $value) {
            $string = str_replace($key, $value ? 'true' : 'false', $string);
        }
        return eval("return (".$string.");");
    }

}

?>
