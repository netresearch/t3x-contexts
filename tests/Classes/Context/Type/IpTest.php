<?php
require_once __DIR__ . '../../../../TestBase.php';
require_once __DIR__ . '../../../../../Classes/Context/Type/Ip.php';

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

class Tx_Contexts_Context_Type_IpTest extends TestBase
{
    public function testMatch()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.14';
        $ipm = $this->getMock(
            'Tx_Contexts_Context_Type_Ip',
            array('getConfValue')
        );
        $ipm->expects($this->at(0))
            ->method('getConfValue')
            ->with($this->equalTo('field_ip'))
            ->will($this->returnValue('192.168.1.14'));
        $ipm->setInvert(false);

        $this->assertTrue($ipm->match());
    }

    public function testMatchInvert()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.14';
        $ipm = $this->getMock(
            'Tx_Contexts_Context_Type_Ip',
            array('getConfValue')
        );
        $ipm->expects($this->at(0))
            ->method('getConfValue')
            ->with($this->equalTo('field_ip'))
            ->will($this->returnValue('192.168.1.14'));
        $ipm->setInvert(true);

        $this->assertFalse($ipm->match());
    }

    public function testMatchNoConfiguration()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.20';
        $ipm = $this->getMock(
            'Tx_Contexts_Context_Type_Ip',
            array('getConfValue')
        );
        $ipm->expects($this->any())
            ->method('getConfValue')
            ->will($this->returnValue(''));

        $this->assertFalse($ipm->match());
    }

    public function testMatchInvalidIp()
    {
        $_SERVER['REMOTE_ADDR'] = '';
        $ipm = $this->getMock(
            'Tx_Contexts_Context_Type_Ip',
            array('getConfValue')
        );
        $ipm->setInvert(false);

        $this->assertFalse($ipm->match());
    }

    /**
     * @dataProvider addressProvider
     */
    public function testIsIpInRange($ip, $range, $res)
    {
        $instance = new Tx_Contexts_Context_Type_Ip();

        $this->assertSame(
            $res,
            $this->callProtected(
                $instance,
                'isIpInRange',
                $ip,
                filter_var(
                    $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4
                ) !== false,
                $range
            )
        );
    }

    public static function addressProvider()
    {
        return array(
            array('80.76.201.37', '80.76.201.32/27', true),
            array('FE80:FFFF:0:FFFF:129:144:52:38', "FE80::/16", true),
            array('80.76.202.37', '80.76.201.32/27', false),
            array('FE80:FFFF:0:FFFF:129:144:52:38', "FE80::/128", false),
            array('80.76.201.37', '', false),
            array('80.76.201', '', false),

            array('80.76.201.37', '80.76.201.*', true),
            array('80.76.201.37', '80.76.*.*', true),
            array('80.76.201.37', '80.76.*', true),
            array('80.76.201.37', '80.76.*.37', true),
            array('80.76.201.37', '80.76.*.40', false),
        );
    }
}

?>
