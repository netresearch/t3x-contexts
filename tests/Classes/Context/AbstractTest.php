<?php
require_once __DIR__ . '/../../TestBase.php';
require_once __DIR__ . '/../../../Classes/Context/Abstract.php';

require_once __DIR__ . '/../../../../../../t3lib/class.t3lib_div.php';

class Tx_Contexts_Context_AbstractTest extends TestBase
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

        $stub->setUseSession($configRet);

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
}

class test extends Tx_Contexts_Context_Abstract
{
    public function match(array $arDependencies = array())
    {
    }
}

?>
