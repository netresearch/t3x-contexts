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

namespace Netresearch\Contexts\Tests\Unit\EventListener;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Netresearch\Contexts\EventListener\TypoScriptConfigEventListener;
use Netresearch\Contexts\Service\QueryParameterService;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for TypoScriptConfigEventListener.
 *
 * The listener replaces the SC_OPTIONS 'configArrayPostProc' hook removed in
 * TYPO3 v13.0 (#102932): the GET parameters that switch the context have to end
 * up in "config.linkVars", otherwise following any link drops the channel.
 */
final class TypoScriptConfigEventListenerTest extends UnitTestCase
{
    #[Test]
    public function addsTheContextParameterToLinkVars(): void
    {
        $event = $this->dispatch(null, ['channel']);

        self::assertSame('channel', $this->linkVars($event));
    }

    #[Test]
    public function appendsToExistingLinkVars(): void
    {
        $event = $this->dispatch('L(1,2)', ['channel']);

        self::assertSame('L(1,2),channel', $this->linkVars($event));
    }

    #[Test]
    public function doesNotDuplicateAnAlreadyListedParameter(): void
    {
        $event = $this->dispatch('channel', ['channel']);

        self::assertSame('channel', $this->linkVars($event));
    }

    #[Test]
    public function doesNotDuplicateAParameterCarryingAValueRestriction(): void
    {
        $event = $this->dispatch('L(1,2),channel(mobile|desktop)', ['channel']);

        self::assertSame('L(1,2),channel(mobile|desktop)', $this->linkVars($event));
    }

    #[Test]
    public function doesNotMatchAParameterThatIsOnlyASubstring(): void
    {
        $event = $this->dispatch('channelId', ['channel']);

        self::assertSame('channelId,channel', $this->linkVars($event));
    }

    #[Test]
    public function addsEveryConfiguredParameter(): void
    {
        $event = $this->dispatch('L', ['affID', 'channel']);

        self::assertSame('L,affID,channel', $this->linkVars($event));
    }

    #[Test]
    public function leavesLinkVarsUntouchedWithoutQueryParameterContexts(): void
    {
        $event = $this->dispatch('L', []);

        self::assertSame('L', $this->linkVars($event));
    }

    #[Test]
    public function doesNotCreateLinkVarsWithoutQueryParameterContexts(): void
    {
        $event = $this->dispatch(null, []);

        self::assertNull(
            $event->getConfigTree()->getChildByName('linkVars'),
            'No contexts means no reason to touch config.linkVars at all',
        );
    }

    /**
     * @param list<string> $parameterNames
     */
    private function dispatch(?string $existingLinkVars, array $parameterNames): ModifyTypoScriptConfigEvent
    {
        $configTree = new RootNode();

        if ($existingLinkVars !== null) {
            $linkVars = new ChildNode('linkVars');
            $linkVars->setValue($existingLinkVars);
            $configTree->addChild($linkVars);
        }

        $event = new ModifyTypoScriptConfigEvent(
            new ServerRequest('https://example.com/'),
            new RootNode(),
            $configTree,
        );

        (new TypoScriptConfigEventListener($this->createQueryParameterService($parameterNames)))($event);

        return $event;
    }

    private function linkVars(ModifyTypoScriptConfigEvent $event): ?string
    {
        return $event->getConfigTree()->getChildByName('linkVars')?->getValue();
    }

    /**
     * @param list<string> $parameterNames
     */
    private function createQueryParameterService(array $parameterNames): QueryParameterService
    {
        $contexts = array_map(
            static fn(string $name): QueryParameterContext => new class ($name) extends QueryParameterContext {
                public function __construct(private readonly string $mockParameterName)
                {
                    $this->use_session = false;
                }

                protected function getConfValue(
                    string $fieldNameArg,
                    string $default = '',
                    string $sheet = 'sDEF',
                    string $lang = 'lDEF',
                    string $value = 'vDEF',
                ): string {
                    return $fieldNameArg === 'field_name' ? $this->mockParameterName : $default;
                }
            },
            $parameterNames,
        );

        return new class ($contexts) extends QueryParameterService {
            /**
             * @param list<QueryParameterContext> $mockContexts
             */
            public function __construct(private readonly array $mockContexts)
            {
            }

            /**
             * @return list<AbstractContext>
             */
            protected function getAvailableContexts(): array
            {
                return $this->mockContexts;
            }
        };
    }
}
