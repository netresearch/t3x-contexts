<?php

namespace Netresearch\Contexts\Context;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3-2013@netresearch.de>
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
use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\ContextException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context factory.
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Factory
{
    /**
     * Find the right class for the context type and instantiate it.
     *
     * @param array $arRow Database context row
     *
     * @throws \Netresearch\Contexts\ContextException
     *
     * @return AbstractContext|null
     */
    public static function createFromDb($arRow)
    {
        $classMap = Configuration::getContextTypes();

        $type = $arRow['type'];

        if (!$type || !array_key_exists($type, $classMap)) {
            GeneralUtility::devLog(
                'No class found for context type "'.$type.'"',
                'tx_contexts', 2
            );
            $type = 'default';
        }

        if (!isset($classMap[$type]['class'])) {
            return;
        }
        $class = $classMap[$type]['class'];
        if (!$class) {
            return;
        }

        $instance = GeneralUtility::makeInstance($class, $arRow);
        if ($instance instanceof SingletonInterface) {
            throw new ContextException($class.' may not be singleton');
        }
        if (!$instance instanceof AbstractContext) {
            throw new ContextException(
                $class.' must extend Tx_Contexts_Context_Abstract'
            );
        }

        return $instance;
    }
}
