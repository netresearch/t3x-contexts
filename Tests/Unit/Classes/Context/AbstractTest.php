<?php

namespace Netresearch\Contexts\Tests\Unit\Context;


class AbstractTest extends \Netresearch\Contexts\Tests\Unit\TestBase
{
    /**
     * @dataProvider SessionProvider
     */
    public function testGetMatchFromSession($configRet, $sessionRet, $expected)
    {
        $stub = $this->getAccessibleMock(
                \Netresearch\Contexts\Tests\Unit\Context\test::class,
                ['getConfValue', 'getSession']
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
        $_SERVER[\Netresearch\Contexts\Context\AbstractContext::REMOTE_ADDR] = '1.1.1.1';
        $_SERVER[\Netresearch\Contexts\Context\AbstractContext::HTTP_X_FORWARDED_FOR]
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

class test extends \Netresearch\Contexts\Context\AbstractContext
{
    public function match(array $arDependencies = array())
    {
    }
}
