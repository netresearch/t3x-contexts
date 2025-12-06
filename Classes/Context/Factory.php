<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context;

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\ContextException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Context factory
 *
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class Factory implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * Find the right class for the context type and instantiate it
     *
     * @param array $arRow Database context row
     *
     * @throws ContextException
     */
    public function createFromDb(array $arRow): ?AbstractContext
    {
        $classMap = Configuration::getContextTypes();
        $type = $arRow['type'];

        if (!$type || !\array_key_exists($type, $classMap)) {
            if ($this->logger !== null) {
                $this->logger->warning('tx_contexts: No class found for context type "' . $type . '"');
            }

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
            throw new ContextException($class . ' may not be singleton', 7787129296);
        }

        if (!$instance instanceof AbstractContext) {
            throw new ContextException(
                $class . ' must extend Tx_Contexts_Context_Abstract',
                7017624821,
            );
        }

        return $instance;
    }
}
