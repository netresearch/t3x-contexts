<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * Base class for unit tests.
 *
 * Provides utility methods for testing protected/private methods
 * and creating accessible mock objects.
 */
abstract class TestBase extends TestCase
{
    /**
     * Make a protected/private method accessible.
     *
     * @param class-string $className Class name
     * @param string $methodName Method name
     */
    protected function getAccessibleMethod(string $className, string $methodName): ReflectionMethod
    {
        $reflectedClass = new ReflectionClass($className);
        $method = $reflectedClass->getMethod($methodName);

        return $method;
    }

    /**
     * Call a protected method on an object and return the result.
     *
     * @param object $object Object the method should be called on
     * @param string $methodName Method to be called
     * @param mixed ...$params Method parameters
     * @return mixed Whatever the method returns
     */
    protected function callProtected(object $object, string $methodName, mixed ...$params): mixed
    {
        $method = $this->getAccessibleMethod($object::class, $methodName);

        return $method->invokeArgs($object, $params);
    }

    /**
     * Get an accessible mock for a class.
     *
     * @param class-string           $className The class to mock
     * @param list<non-empty-string> $methods   Methods to mock
     */
    protected function getAccessibleMock(string $className, array $methods = []): MockObject
    {
        return $this->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }
}
