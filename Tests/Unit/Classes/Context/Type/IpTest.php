<?php

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

class IpTest extends \Netresearch\Contexts\Tests\Unit\TestBase
{
    public function testMatch()
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.14';
        $ipm = $this->getMock(
            '\Netresearch\Contexts\Context\Type\IpContext',
            ['getConfValue']
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
            '\Netresearch\Contexts\Context\Type\IpContext',
            ['getConfValue']
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
            '\Netresearch\Contexts\Context\Type\IpContext',
            ['getConfValue']
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
            '\Netresearch\Contexts\Context\Type\IpContext',
            ['getConfValue']
        );
        $ipm->setInvert(false);

        $this->assertFalse($ipm->match());
    }

    /**
     * @dataProvider addressProvider
     */
    public function testIsIpInRange($ip, $range, $res)
    {
        $instance = new \Netresearch\Contexts\Context\Type\IpContext();

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
        return [
            ['80.76.201.37', '80.76.201.32/27', true],
            ['FE80:FFFF:0:FFFF:129:144:52:38', 'FE80::/16', true],
            ['80.76.202.37', '80.76.201.32/27', false],
            ['FE80:FFFF:0:FFFF:129:144:52:38', 'FE80::/128', false],
            ['80.76.201.37', '', false],
            ['80.76.201', '', false],

            ['80.76.201.37', '80.76.201.*', true],
            ['80.76.201.37', '80.76.*.*', true],
            ['80.76.201.37', '80.76.*', true],
            ['80.76.201.37', '80.76.*.37', true],
            ['80.76.201.37', '80.76.*.40', false],
        ];
    }
}
