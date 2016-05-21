<?php
namespace Bmack\Contexts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
*  (c) 2016 Benjamin Mack <benjamin.mack@b13.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
use Bmack\Contexts\Context\Container;

/**
 * Load context by alias.
 * Caches results.
 *
 * @author     André Hähnel <andre.haehnel@netresearch.de>
 */
class ContextMatcher
{
    /**
     * Singleton instance
     *
     * @var ContextMatcher
     */
    protected static $instance;

    /**
     * Match results. Alias => boolean match result
     *
     * @var array
     */
    protected $arMatches = array();

    /**
     * Singleton
     *
     * @return self One instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Unsets this instance
     *
     * @return void
     */
    public static function clearInstance()
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
    public function matches($strContext)
    {
        if (isset($this->arMatches[$strContext])) {
            return $this->arMatches[$strContext];
        }

        $context = Container::get()->find($strContext);
        $this->arMatches[$strContext] = $context !== null;

        return $this->arMatches[$strContext];
    }
}
