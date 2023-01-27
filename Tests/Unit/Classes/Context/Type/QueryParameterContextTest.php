<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class QueryParameterContextTest extends UnitTestCase
{
    public function setUp()
    {
        error_reporting(error_reporting() & ~E_NOTICE);

        foreach ($_GET as $key => $dummy) {
            unset($_GET[$key]);
        }
    }

    public function testMatchParameterMissing()
    {
        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertFalse($getm->match(), 'No parameter means no match');
    }

    public function testMatchParameterNoValue()
    {
        $_GET['affID'] = '';

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertFalse($getm->match(), 'No value means no match');
    }

    public function testMatchParameterCorrectValue()
    {
        $_GET['affID'] = 123;

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', '123'),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertTrue($getm->match(), 'Correct value');
    }

    public function testMatchParameterCorrectValueOfMany()
    {
        $_GET['affID'] = 125;

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array(
                'field_values'     , null, 'sDEF', 'lDEF', 'vDEF',
                "123\n124\n125\n"
            ),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertTrue($getm->match(), 'Correct value');
    }

    public function testMatchParameterWrongValueOfMany()
    {
        $_GET['affID'] = 124125;

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array(
                'field_values'     , null, 'sDEF', 'lDEF', 'vDEF',
                "123\n124\n125\n"
            ),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertFalse($getm->match(), 'value is not allowed');
    }

    public function testMatchParameterAnyValue()
    {
        $_GET['affID'] = 'aslkfj';

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertTrue($getm->match(), 'Any value is correct');
    }

    public function testMatchParameterAnyValueMissing()
    {
        unset($_GET['affID']);

        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', 'affID'),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $this->assertFalse($getm->match(), 'Any value is missing');
    }

    /**
     * @expectedException Exception
     */
    public function testMatchUnconfiguredNoParameter()
    {
        $getm = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\QueryParameterContext',
            array('getConfValue')
        );
        $getm->setUseSession(false);
        $retValMap = array(
            array('field_name'       , null, 'sDEF', 'lDEF', 'vDEF', ''),
            array('field_values'     , null, 'sDEF', 'lDEF', 'vDEF', ''),
        );

        $getm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValueMap($retValMap));

        $getm->match();
    }

}
?>
