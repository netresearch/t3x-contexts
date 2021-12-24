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
use Psr\Log\LoggerAwareTrait;

/**
 * Context factory
 *
 * @author Christian Opitz <christian.opitz@netresearch.de>
 */
class Factory implements \Psr\Log\LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Find the right class for the context type and instantiate it
     *
     * @param array $arRow Database context row
     *
     * @return AbstractContext|null
     * @throws \Netresearch\Contexts\ContextException
     */
    public function createFromDb($arRow)
    {
        $classMap = Configuration::getContextTypes();

        $type     = $arRow['type'];

        if (!$type || !array_key_exists($type, $classMap)) {
            $this->logger->warning('tx_contexts: No class found for context type "' . $type . '"');
            $type = 'default';
        }

        if (!isset($classMap[$type]['class'])) {
            return null;
        }
        $class = $classMap[$type]['class'];
        if (!$class) {
            return null;
        }

        $instance = GeneralUtility::makeInstance($class, $arRow);
        if ($instance instanceof SingletonInterface) {
            throw new ContextException($class . ' may not be singleton');
        }
        if (!$instance instanceof AbstractContext) {
            throw new ContextException(
                $class . ' must extend Tx_Contexts_Context_Abstract'
            );
        }
        return $instance;
    }
}
