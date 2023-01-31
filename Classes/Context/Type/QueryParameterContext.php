<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context\Type;

use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Service\FrontendControllerService;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use function array_key_exists;
use function count;
use function in_array;

/**
 * Matches on a GET parameter with a certain value
 *
 * @author     Christian Weiske <christian.weiske@netresearch.de>
 * @author     Christian Opitz <christian.opitz@netresearch.de>
 * @license    http://opensource.org/licenses/gpl-license GPLv2 or later
 */
class QueryParameterContext extends AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     *
     * @throws RuntimeException
     */
    public function match(array $arDependencies = []): bool
    {
        $configValue = $this->getConfValue('field_name');
        $param       = trim($configValue ?? '');

        if ($param === '') {
            throw new RuntimeException(
                'Parameter name missing from GET Parameter context configuration'
            );
        }

        if (!array_key_exists($param, $_GET)) {
            //load from session if no param given
            [$bUseMatch, $bMatch] = $this->getMatchFromSession();
            return $this->invert($bUseMatch && $bMatch);
        }

        $value = GeneralUtility::_GET($param);

        // Register param on TSFE service for cache and linkVars management
        FrontendControllerService::registerQueryParameter(
            $param,
            $value,
            !$this->use_session
        );

        $values = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values') ?? '',
            true
        );

        return $this->invert($this->storeInSession(
            (count($values) === 0) || in_array($value, $values, true)
        ));
    }
}
