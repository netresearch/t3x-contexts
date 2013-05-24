<?php
require_once __DIR__ . '../../../../Classes/Context/Abstract.php';

require_once __DIR__ . '../../../../../../../t3lib/class.t3lib_div.php';

if (!class_exists('t3lib_div')
    && class_exists('TYPO3\CMS\Core\Utility\GeneralUtility')
) {
    class t3lib_div extends TYPO3\CMS\Core\Utility\GeneralUtility
    {
    }
}

class Tx_Contexts_Context_AbstractTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider SessionProvider
     */
    public function testGetMatchFromSession($configRet, $sessionRet, $expected)
    {
         $stub = $this->getMock(
            'test',
            array(
                'getConfValue',
                'getSession'
            )
        );

        $stub->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValue($configRet));

        $stub->expects($this->any())
            ->method('getSession')
            ->will($this->returnValue($sessionRet));

        $test = $this->callProtected($stub, 'getMatchFromSession');
        $this->assertSame($expected, $test);

    }


    public static function SessionProvider()
    {
        return array(
            //no use no session
            array('0',null, array(false,null)),
             //no use and session set
            array('0',true, array(false,null)),
            // use session but not set
            array('1',null, array(false,null)),
            //use and session set
            array('1',true, array(true,true)),
            //use and session set
            array('1',false, array(true,false)),
             //use and session set
            array('1','whatever', array(true,true)),
        );
    }

    /**
     * Make a proteced/private method accessible.
     *
     * @param string $strClass      Class name
     * @param string $strMethodName Method name
     *
     * @return ReflectionMethod
     */
    public function getAccessibleMethod($strClass, $strMethodName)
    {
        $reflectedClass = new ReflectionClass($strClass);

        /* @var $method ReflectionMethod */
        $method = $reflectedClass->getMethod($strMethodName);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Call a protected method on an object and return the result
     *
     * @param object $obj           Object the method should be called on
     * @param string $strMethodName Method to be called
     * @param mixed  $param1        First method parameter
     * @param mixed  $param2        Second method parameter
     * @param mixed  ...            ...
     *
     * @return mixed Whatever the method returns
     */
    public function callProtected($obj, $strMethodName)
    {
        $params = func_get_args();
        array_shift($params);
        array_shift($params);
        $m = $this->getAccessibleMethod(get_class($obj), $strMethodName);
        return $m->invokeArgs($obj, $params);
    }
}

class test extends Tx_Contexts_Context_Abstract
{
    public function match(array $arDependencies = array()){}
}

?>
