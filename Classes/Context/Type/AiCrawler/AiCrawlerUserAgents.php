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

namespace Netresearch\Contexts\Context\Type\AiCrawler;

use function is_array;
use function is_string;

/**
 * User-agent tokens of AI crawlers and AI fetchers, as published by their
 * vendors.
 *
 * Every entry is the name the vendor documents for its crawler or fetcher.
 * OpenAI and Perplexity publish full User-Agent strings containing that name;
 * Anthropic publishes the bot names only. Sources (last verified 2026-11-06):
 *
 * - OpenAI: https://developers.openai.com/api/docs/bots
 * - Anthropic: https://support.claude.com/en/articles/8896518-does-anthropic-crawl-data-from-the-web-and-how-can-site-owners-block-the-crawler
 * - Perplexity: https://docs.perplexity.ai/guides/bots
 *
 * Integrators add tokens without a release of this extension through
 * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'],
 * a list of strings.
 *
 * @license AGPL-3.0-or-later
 */
final class AiCrawlerUserAgents
{
    /**
     * Built-in tokens, keyed by token, with the vendor-documented purpose.
     *
     * @var array<string, string>
     */
    public const BUILT_IN = [
        // OpenAI
        'GPTBot'        => 'OpenAI: crawls content that may be used to train foundation models',
        'OAI-SearchBot' => 'OpenAI: indexes sites for ChatGPT search',
        'ChatGPT-User'  => 'OpenAI: fetches a page for a ChatGPT user action',
        // Anthropic
        'ClaudeBot'        => 'Anthropic: collects content that may be used for model training',
        'Claude-SearchBot' => 'Anthropic: indexes content to improve search answers',
        'Claude-User'      => 'Anthropic: fetches a page when a user asks Claude a question',
        // Perplexity
        'PerplexityBot'   => 'Perplexity: surfaces and links websites in Perplexity search results',
        'Perplexity-User' => 'Perplexity: visits a page when a user asks a question',
    ];

    /**
     * All tokens: the built-in ones plus those configured in
     * $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'].
     * Entries that are not non-empty strings are ignored.
     *
     * @return list<string>
     */
    public static function getAll(): array
    {
        $tokens = array_keys(self::BUILT_IN);

        $configured = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['contexts']['aiCrawlerUserAgents'] ?? [];
        if (!is_array($configured)) {
            return $tokens;
        }

        foreach ($configured as $token) {
            if (is_string($token) && trim($token) !== '') {
                $tokens[] = trim($token);
            }
        }

        return array_values(array_unique($tokens));
    }
}
