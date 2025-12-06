<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Factory;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Netresearch\Contexts\ContextException;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for Factory class.
 *
 * Factory creates context instances from database rows, mapping the 'type' field
 * to the appropriate context class.
 */
final class FactoryTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up TCA for context types
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes'] = [
            'getparam' => [
                'title' => 'GET Parameter',
                'class' => QueryParameterContext::class,
                'flexFile' => 'EXT:contexts/Configuration/FlexForms/GetParam.xml',
            ],
            'default' => [
                'title' => 'Default',
                'class' => null,
                'flexFile' => '',
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']);
        parent::tearDown();
    }

    #[Test]
    public function createFromDbReturnsContextInstanceForValidType(): void
    {
        $factory = new Factory();

        $row = [
            'uid' => 1,
            'pid' => 0,
            'type' => 'getparam',
            'title' => 'Test Context',
            'alias' => 'test',
            'type_conf' => '',
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'tstamp' => time(),
        ];

        $context = $factory->createFromDb($row);

        self::assertInstanceOf(AbstractContext::class, $context);
        self::assertInstanceOf(QueryParameterContext::class, $context);
    }

    #[Test]
    public function createFromDbReturnsNullForUnknownType(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(self::stringContains('No class found for context type'));

        $factory = new Factory();
        $factory->setLogger($logger);

        $row = [
            'uid' => 1,
            'type' => 'unknown_type',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
        ];

        $result = $factory->createFromDb($row);

        self::assertNull($result);
    }

    #[Test]
    public function createFromDbReturnsNullForEmptyType(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning');

        $factory = new Factory();
        $factory->setLogger($logger);

        $row = [
            'uid' => 1,
            'type' => '',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
        ];

        $result = $factory->createFromDb($row);

        self::assertNull($result);
    }

    #[Test]
    public function createFromDbReturnsNullWhenClassNotSet(): void
    {
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']['noclasstype'] = [
            'title' => 'No Class Type',
            'class' => null,
            'flexFile' => '',
        ];

        $factory = new Factory();

        $row = [
            'uid' => 1,
            'type' => 'noclasstype',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
        ];

        $result = $factory->createFromDb($row);

        self::assertNull($result);
    }

    #[Test]
    public function createFromDbThrowsExceptionForSingletonClass(): void
    {
        // Register a singleton class as context type (which is not allowed)
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']['singleton'] = [
            'title' => 'Singleton Test',
            'class' => SingletonContextStub::class,
            'flexFile' => '',
        ];

        $factory = new Factory();

        $row = [
            'uid' => 1,
            'type' => 'singleton',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'tstamp' => time(),
        ];

        $this->expectException(ContextException::class);
        $this->expectExceptionMessage('may not be singleton');
        $this->expectExceptionCode(7787129296);

        $factory->createFromDb($row);
    }

    #[Test]
    public function createFromDbThrowsExceptionForNonContextClass(): void
    {
        // Register a class that doesn't extend AbstractContext
        $GLOBALS['TCA']['tx_contexts_contexts']['contextTypes']['invalid'] = [
            'title' => 'Invalid Class',
            'class' => InvalidContextStub::class,
            'flexFile' => '',
        ];

        $factory = new Factory();

        $row = [
            'uid' => 1,
            'type' => 'invalid',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'tstamp' => time(),
        ];

        $this->expectException(ContextException::class);
        $this->expectExceptionMessage('must extend');
        $this->expectExceptionCode(7017624821);

        $factory->createFromDb($row);
    }

    #[Test]
    public function createFromDbFallsBackToDefaultForUnknownType(): void
    {
        // Default type has no class, so should return null
        $factory = new Factory();
        $factory->setLogger($this->createMock(LoggerInterface::class));

        $row = [
            'uid' => 1,
            'type' => 'totally_unknown',
            'title' => 'Test',
            'alias' => 'test',
            'type_conf' => '',
        ];

        $result = $factory->createFromDb($row);

        self::assertNull($result);
    }
}

/**
 * Stub class that implements SingletonInterface (not allowed for contexts).
 */
class SingletonContextStub extends AbstractContext implements \TYPO3\CMS\Core\SingletonInterface
{
    public function match(array $arDependencies = []): bool
    {
        return true;
    }
}

/**
 * Stub class that doesn't extend AbstractContext (not allowed).
 */
class InvalidContextStub
{
    public function __construct(array $row)
    {
    }
}
