<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Api;

use Netresearch\Contexts\Context\Container;

/**
 * Load context by alias.
 * Caches results.
 *
 * @author  André Hähnel <andre.haehnel@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ContextMatcher
{
    /**
     * Singleton instance
     *
     */
    protected static ?ContextMatcher $instance = null;

    /**
     * Match results. Alias => boolean match result
     *
     */
    protected array $arMatches = [];

    /**
     * Singleton
     *
     * @return self One instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Unsets this instance
     *
     */
    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    /**
     * Match context by alias
     *
     * @param string $strContext alias from context entry
     *
     * @return bool TRUE if context matches, FALSE if not
     */
    public function matches(string $strContext): bool
    {
        if (isset($this->arMatches[$strContext])) {
            return $this->arMatches[$strContext];
        }

        $context = Container::get()->find($strContext);
        $this->arMatches[$strContext] = $context !== null;

        return $this->arMatches[$strContext];
    }
}
