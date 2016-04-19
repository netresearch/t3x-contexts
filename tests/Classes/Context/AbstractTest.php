<?php
require_once __DIR__ . '/../../TestBase.php';
require_once __DIR__ . '/../../../Classes/Context/Abstract.php';

$rootDir = __DIR__ . '/../../../../../../';
if (is_dir($rootDir . '/t3lib')) {
    require_once $rootDir . '/t3lib/class.t3lib_div.php';
} else {
    require_once $rootDir . '/typo3/sysext/core/Classes/Utility/GeneralUtility.php';
}

if (!class_exists('t3lib_div')
    && class_exists('TYPO3\CMS\Core\Utility\GeneralUtility')
) {
    /**
     * Class t3lib_div
     * @internal
     */
    class t3lib_div extends TYPO3\CMS\Core\Utility\GeneralUtility
    {
    }
}

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

    /**
     * Test that the remote address returns the correct value
     *
     * @return void
     */
    public function testGetRemoteAddressWithProxyGetFirst()
    {
        $test = new test();
        global $TYPO3_CONF_VARS;
        $TYPO3_CONF_VARS = array(
            'SYS' => array(
                'reverseProxyIP'               => '1.1.1.1',
                'reverseProxyHeaderMultiValue' => 'first'
            )
        );
        $_SERVER[Tx_Contexts_Context_Abstract::REMOTE_ADDR] = '1.1.1.1';
        $_SERVER[Tx_Contexts_Context_Abstract::HTTP_X_FORWARDED_FOR]
            = '1.2.3.4';
        $this->assertSame(
            '1.2.3.4',
            $this->callProtected(
                $test,
                'getRemoteAddress'
            )
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
