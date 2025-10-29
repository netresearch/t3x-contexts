<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Netresearch\Contexts\Tests\Unit;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

abstract class TestBase extends UnitTestCase
{
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
        $reflectedClass = new \ReflectionClass($strClass);

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
