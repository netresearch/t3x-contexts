<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Classes\Context\Type;

class CombinationTest extends \Netresearch\Contexts\Tests\Unit\TestBase
{
    public function testGetDependenciesSucces()
    {
        $abstractMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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


        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            )
        );


        $instance->expects(self::once())
            ->method('getConfValue')
            ->will(self::returnValue('(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST '));

        $arTest = $instance->getDependencies(
            array(
                123 => $abstractMock,
                125 => $instance,
            )
        );

        self::assertArrayHasKey(123, $arTest);
        self::assertEquals(array(123 => true), $arTest);
    }

    public function testGetDependenciesSuccesWithDisabled()
    {
        $abstractMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
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


        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            )
        );


        $instance->expects(self::once())
            ->method('getConfValue')
            ->will(self::returnValue('(UNITTEST && UNITTEST || UNITTEST) xor >< UNITTEST '));

        $arTest = $instance->getDependencies(
            array(
                123 => $abstractMock,
                125 => $instance,
            )
        );

        self::assertArrayHasKey(123, $arTest);
        self::assertEquals(array(123 => false), $arTest);
    }


    public function testGetDependenciesEmpty()
    {

        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            )
        );


        $instance->expects(self::once())
            ->method('getConfValue')
            ->will(self::returnValue('(context1 && context2 || context3) xor >< context5 '));

        $arTest = $instance->getDependencies(
            array(125 => $instance)
        );

        self::assertEmpty($arTest);
    }

    public function testMatchSuccess()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $ipContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 124,
                    'type' => 'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $getContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));

        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            )
        );
        $container = $this->createMock(
            '\Netresearch\Contexts\Context\Container',
            array()
        );

        $arContexts = array(
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        );

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->will(self::returnValue('UNITTEST && getUNITTEST'));

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(
            array(
                123 => $ipContextMock,
                124 => $getContextMock,
                125 => $instance,
            ),
            $matched
        );
    }

    public function testMatchSuccessWithDisabled()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $ipContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 124,
                    'type' => 'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
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
        $getContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));

        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
            )
        );
        $container = $this->createMock(
            '\Netresearch\Contexts\Context\Container',
            array()
        );

        $arContexts = array(
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        );

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->will(self::returnValue('UNITTEST && getUNITTEST'));

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(
            array(
                123 => $ipContextMock,
                125 => $instance,
            ),
            $matched
        );
    }

    public function testMatchFailed()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $ipContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(false));
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 124,
                    'type' => 'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $getContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));

        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
                'findInContainer',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
                'hide_in_backend' => false,
            )
        );
        $container = $this->createMock(
            '\Netresearch\Contexts\Context\Container',
            array()
        );

        $arContexts = array(
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        );

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->will(self::returnValue('UNITTEST && getUNITTEST'));

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(array(124 => $getContextMock), $matched);
    }

    public function testMatchFailedWithDisabled()
    {
        $ipContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 123,
                    'type' => 'ip',
                    'title' => 'UNITTEST',
                    'alias' => 'UNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => false,
                    'hide_in_backend' => false,
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
        $ipContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(false));
        $getContextMock = $this->getMockForAbstractClass(
            '\Netresearch\Contexts\Context\AbstractContext',
            array(
                array(
                    'uid' => 124,
                    'type' => 'getparam',
                    'title' => 'getUNITTEST',
                    'alias' => 'getUNITTEST',
                    'tstamp' => 1234567,
                    'invert' => 0,
                    'use_session' => 0,
                    'type_conf' => '',
                    'disabled' => true,
                    'hide_in_backend' => false,
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
        $getContextMock->expects(self::any())
            ->method('match')
            ->will(self::returnValue(true));

        $instance = $this->getAccessibleMock(
            '\Netresearch\Contexts\Context\Type\CombinationContext',
            array(
                'getConfValue',
                'findInContainer',
            ),
            array(
                'uid' => 125,
                'type' => 'combination',
                'title' => 'combiUNITTEST',
                'alias' => 'combiUNITTEST',
                'type_conf' => '',
                'disabled' => false,
            )
        );
        $container = $this->createMock(
            '\Netresearch\Contexts\Context\Container',
            array()
        );

        $arContexts = array(
            123 => $ipContextMock,
            124 => $getContextMock,
            125 => $instance,
        );

        $instance->expects(self::any())
            ->method('getConfValue')
            ->with(self::equalTo('field_expression'))
            ->will(self::returnValue('UNITTEST && getUNITTEST'));

        $matched = $this->callProtected($container, 'match', $arContexts);
        self::assertEquals(array(), $matched);
    }
}
