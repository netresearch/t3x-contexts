<?php

require_once __DIR__ . '../../../../../Classes/Context/Abstract.php';
require_once __DIR__ . '../../../../../Classes/Context/Type/Combination.php';
require_once __DIR__ . '../../../../../Classes/Context/Container.php';
require_once __DIR__ . '../../../../../Classes/Context/Type/Combination/LogicalExpressionEvaluator.php';


require_once __DIR__ . '../../../../../../../../t3lib/class.t3lib_div.php';


class Tx_Contexts_Context_Type_CombinationTest extends PHPUnit_Framework_TestCase
{

    public function testGetDependenciesSucces()
    {
        $abstractMock = $this->getMockForAbstractClass(
            'Tx_Contexts_Context_Abstract',
            array(
                array(
                    'uid'=>123,
                    'type'=>'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => ''
                )
            ),
            '',
            true,
            true,
            true,
            array(
                '__construct',
            )
        );


       $instance = $this->getMock(
            'Tx_Contexts_Context_Type_Combination',
            array(
                'getConfValue',
                'findInContainer',
            )
        );


       $instance->expects(self::once())
            ->method('getConfValue')
            ->will(self::returnValue('(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST '));

       $instance->expects(self::any())
            ->method('findInContainer')
            ->will(self::returnValue($abstractMock));

       $arTest = $instance->getDependencies();

       self::assertArrayHasKey(123, $arTest);
       self::assertTrue($arTest[123]);

    }


    public function testGetDependenciesEmpty()
    {

       $instance = $this->getMock(
            'Tx_Contexts_Context_Type_Combination',
            array(
                'getConfValue',
                'findInContainer',
            )
        );


       $instance->expects(self::once())
            ->method('getConfValue')
            ->will(self::returnValue('(context1 && context2 || context3) xor >< context5 '));

       $instance->expects(self::any())
            ->method('findInContainer')
            ->will(self::returnValue(null));

       $arTest = $instance->getDependencies();

       self::assertEmpty($arTest);

    }

}

?>
