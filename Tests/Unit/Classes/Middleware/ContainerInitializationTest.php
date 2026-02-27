<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Middleware;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Middleware\ContainerInitialization;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit tests for ContainerInitialization middleware.
 *
 * ContainerInitialization initializes the context Container with the
 * current request and triggers context matching before passing control
 * to the next handler.
 */
final class ContainerInitializationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
    }

    protected function tearDown(): void
    {
        Container::reset();
        parent::tearDown();
    }

    #[Test]
    public function middlewareImplementsMiddlewareInterface(): void
    {
        $middleware = new ContainerInitialization();

        self::assertInstanceOf(MiddlewareInterface::class, $middleware);
    }

    #[Test]
    public function classImplementsMiddlewareInterface(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);

        self::assertTrue(
            $reflection->implementsInterface(MiddlewareInterface::class),
            'ContainerInitialization must implement MiddlewareInterface',
        );
    }

    #[Test]
    public function processMethodExists(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);

        self::assertTrue(
            $reflection->hasMethod('process'),
            'ContainerInitialization must have a process() method',
        );
    }

    #[Test]
    public function processMethodHasCorrectSignature(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);
        $method = $reflection->getMethod('process');
        $parameters = $method->getParameters();

        self::assertCount(2, $parameters, 'process() should accept exactly two parameters');
        self::assertSame('request', $parameters[0]->getName());
        self::assertSame('handler', $parameters[1]->getName());
        self::assertSame(
            ServerRequestInterface::class,
            $parameters[0]->getType()?->getName(),
        );
        self::assertSame(
            RequestHandlerInterface::class,
            $parameters[1]->getType()?->getName(),
        );
    }

    #[Test]
    public function processMethodReturnsResponseInterface(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);
        $method = $reflection->getMethod('process');
        $returnType = $method->getReturnType();

        self::assertNotNull($returnType);
        self::assertSame(ResponseInterface::class, $returnType->getName());
    }

    #[Test]
    public function processDelegatesToHandler(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        // We need a partial mock of ContainerInitialization to stub out the
        // Container::get() singleton call which requires database infrastructure.
        $middleware = $this->getMockBuilder(ContainerInitialization::class)
            ->onlyMethods([])
            ->getMock();

        // The Container::get() call in process() will return the reset container.
        // Container::initMatching() requires DB, so we inject a pre-built container.
        $container = $this->getMockBuilder(Container::class)
            ->onlyMethods(['setRequest', 'initMatching'])
            ->getMock();

        $container->expects(self::once())
            ->method('setRequest')
            ->with($request)
            ->willReturn($container);

        $container->expects(self::once())
            ->method('initMatching')
            ->willReturn($container);

        // Inject the mock container as the singleton
        $reflection = new ReflectionClass(Container::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, $container);

        $result = $middleware->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function processPassesRequestToContainer(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $container = $this->getMockBuilder(Container::class)
            ->onlyMethods(['setRequest', 'initMatching'])
            ->getMock();

        $container->expects(self::once())
            ->method('setRequest')
            ->with(self::identicalTo($request))
            ->willReturn($container);

        $container->method('initMatching')->willReturn($container);

        $reflection = new ReflectionClass(Container::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, $container);

        $middleware = new ContainerInitialization();
        $middleware->process($request, $handler);
    }

    #[Test]
    public function processCallsHandlerWithOriginalRequest(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with(self::identicalTo($request))
            ->willReturn($response);

        $container = $this->getMockBuilder(Container::class)
            ->onlyMethods(['setRequest', 'initMatching'])
            ->getMock();

        $container->method('setRequest')->willReturn($container);
        $container->method('initMatching')->willReturn($container);

        $reflection = new ReflectionClass(Container::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, $container);

        $middleware = new ContainerInitialization();
        $middleware->process($request, $handler);
    }

    #[Test]
    public function processReturnsHandlerResponse(): void
    {
        $expectedResponse = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expectedResponse);

        $container = $this->getMockBuilder(Container::class)
            ->onlyMethods(['setRequest', 'initMatching'])
            ->getMock();

        $container->method('setRequest')->willReturn($container);
        $container->method('initMatching')->willReturn($container);

        $reflection = new ReflectionClass(Container::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, $container);

        $middleware = new ContainerInitialization();
        $result = $middleware->process($request, $handler);

        self::assertSame($expectedResponse, $result);
    }

    #[Test]
    public function middlewareIsNotFinal(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);

        self::assertFalse(
            $reflection->isFinal(),
            'ContainerInitialization should not be final to allow extension',
        );
    }

    #[Test]
    public function middlewareHasNoConstructorParameters(): void
    {
        $reflection = new ReflectionClass(ContainerInitialization::class);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null) {
            self::assertCount(
                0,
                $constructor->getParameters(),
                'ContainerInitialization constructor should accept no parameters',
            );
        }

        // If no constructor is declared, that is also valid (uses parent default)
        self::assertInstanceOf(ContainerInitialization::class, new ContainerInitialization());
    }

    #[Test]
    public function processCallsInitMatchingOnContainer(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $request = $this->createMock(ServerRequestInterface::class);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);

        $container = $this->getMockBuilder(Container::class)
            ->onlyMethods(['setRequest', 'initMatching'])
            ->getMock();

        $container->method('setRequest')->willReturn($container);

        // Verify initMatching is called exactly once
        $container->expects(self::once())
            ->method('initMatching')
            ->willReturn($container);

        $reflection = new ReflectionClass(Container::class);
        $instanceProperty = $reflection->getProperty('instance');
        $instanceProperty->setValue(null, $container);

        $middleware = new ContainerInitialization();
        $middleware->process($request, $handler);
    }
}
