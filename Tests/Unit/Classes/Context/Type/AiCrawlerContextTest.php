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

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Context\Type\AiCrawler\AiCrawlerUserAgents;
use Netresearch\Contexts\Context\Type\AiCrawlerContext;
use Netresearch\Contexts\Tests\Unit\TestBase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Tests for the AI crawler context.
 */
final class AiCrawlerContextTest extends TestBase
{
    /**
     * @var array<string, mixed>
     */
    private array $originalServerVars = [];

    private mixed $originalExtconf = null;

    protected function setUp(): void
    {
        parent::setUp();
        Container::reset();
        unset($GLOBALS['TYPO3_REQUEST']);
        /** @var array<string, mixed> $serverVars */
        $serverVars               = $_SERVER;
        $this->originalServerVars = $serverVars;
        $this->originalExtconf    = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] ?? null;
        unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents']);
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServerVars;
        if ($this->originalExtconf === null) {
            unset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents']);
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] = $this->originalExtconf;
        }

        Container::reset();
        parent::tearDown();
    }

    /**
     * User agents of the built-in crawlers. OpenAI and Perplexity publish
     * full strings, copied here verbatim from their documentation. Anthropic
     * publishes only the bot names, so its strings are built around them.
     *
     * @return array<string, array{0: string}>
     */
    public static function crawlerUserAgentProvider(): array
    {
        return [
            'GPTBot'           => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.4; +https://openai.com/gptbot'],
            'OAI-SearchBot'    => ['Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36; compatible; OAI-SearchBot/1.4; +https://openai.com/searchbot'],
            'ChatGPT-User'     => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; ChatGPT-User/1.0; +https://openai.com/bot'],
            'ClaudeBot'        => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; ClaudeBot/1.0)'],
            'Claude-SearchBot' => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-SearchBot/1.0)'],
            'Claude-User'      => ['Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko; compatible; Claude-User/1.0)'],
            'PerplexityBot'    => ['Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)'],
            'Perplexity-User'  => ['Mozilla/5.0 (compatible; Perplexity-User/1.0; +https://perplexity.ai/perplexity-user)'],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function otherUserAgentProvider(): array
    {
        return [
            'desktop Chrome' => ['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'],
            'Googlebot'      => ['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'],
            'curl'           => ['curl/8.5.0'],
        ];
    }

    #[Test]
    public function everyBuiltInTokenHasATestCase(): void
    {
        self::assertSame(
            array_keys(AiCrawlerUserAgents::BUILT_IN),
            array_keys(self::crawlerUserAgentProvider()),
        );
    }

    #[Test]
    #[DataProvider('crawlerUserAgentProvider')]
    public function matchReturnsTrueForBuiltInCrawler(string $userAgent): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        self::assertTrue($this->createContext()->match());
    }

    #[Test]
    #[DataProvider('otherUserAgentProvider')]
    public function matchReturnsFalseForOtherUserAgent(string $userAgent): void
    {
        $_SERVER['HTTP_USER_AGENT'] = $userAgent;

        self::assertFalse($this->createContext()->match());
    }

    #[Test]
    public function matchReadsUserAgentFromPsr7Request(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('hasHeader')->willReturnCallback(
            static fn (string $name): bool => strtolower($name) === 'user-agent',
        );
        $request->method('getHeaderLine')->willReturn(
            'Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.4; +https://openai.com/gptbot',
        );
        Container::get()->setRequest($request);

        self::assertTrue($this->createContext()->match());
    }

    #[Test]
    public function matchIsCaseInsensitive(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'mozilla/5.0 (compatible; gptbot/1.4)';

        self::assertTrue($this->createContext()->match());
    }

    #[Test]
    public function matchReturnsFalseWithoutUserAgent(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        self::assertFalse($this->createContext()->match());
    }

    #[Test]
    public function matchReturnsFalseForEmptyUserAgentEvenWithEmptyAdditionalField(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = '';

        self::assertFalse($this->createContext()->match());
    }

    #[Test]
    public function invertTurnsCrawlerMatchIntoNoMatch(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)';

        self::assertFalse($this->createContext('', true)->match());
    }

    #[Test]
    public function invertTurnsMissingUserAgentIntoMatch(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);

        self::assertTrue($this->createContext('', true)->match());
    }

    #[Test]
    public function matchUsesAdditionalUserAgentsOfTheRecord(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; ExampleAiBot/2.0)';

        self::assertFalse($this->createContext()->match());
        self::assertTrue($this->createContext(" \nExampleAiBot\n")->match());
    }

    #[Test]
    public function matchUsesUserAgentsConfiguredInExtconf(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; ExampleAiBot/2.0)';

        self::assertFalse($this->createContext()->match());

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] = ['ExampleAiBot'];

        self::assertTrue($this->createContext()->match());
    }

    #[Test]
    public function getAllAppendsValidConfiguredTokensAndIgnoresInvalidOnes(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] = [
            ' ExampleAiBot ',
            '',
            '   ',
            42,
            null,
            'GPTBot',
        ];

        self::assertSame(
            [...array_keys(AiCrawlerUserAgents::BUILT_IN), 'ExampleAiBot'],
            AiCrawlerUserAgents::getAll(),
        );
    }

    #[Test]
    public function getAllIgnoresConfigurationThatIsNotAList(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] = 'ExampleAiBot';

        self::assertSame(array_keys(AiCrawlerUserAgents::BUILT_IN), AiCrawlerUserAgents::getAll());
    }

    /**
     * @return MockObject&AiCrawlerContext
     */
    private function createContext(string $additionalUserAgents = '', bool $invert = false): MockObject
    {
        $mock = $this->getMockBuilder(AiCrawlerContext::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getConfValue'])
            ->getMock();

        $mock->method('getConfValue')
            ->willReturnMap([
                ['field_additional_user_agents', '', 'sDEF', 'lDEF', 'vDEF', $additionalUserAgents],
            ]);

        $mock->setInvert($invert);
        $mock->setUseSession(false);

        return $mock;
    }
}
