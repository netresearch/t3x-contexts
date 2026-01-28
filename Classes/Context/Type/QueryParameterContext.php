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
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\Service\FrontendControllerService;
use RuntimeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches on a GET parameter with a certain value
 *
 * @author  Christian Weiske <christian.weiske@netresearch.de>
 * @author  Christian Opitz <christian.opitz@netresearch.de>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
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
        $param = trim($configValue);

        if ($param === '') {
            throw new RuntimeException(
                'Parameter name missing from GET Parameter context configuration',
                3404190205,
            );
        }

        $queryParams = $this->getQueryParams();

        if (!\array_key_exists($param, $queryParams)) {
            // load from session if no param given
            [$bUseMatch, $bMatch] = $this->getMatchFromSession();
            return $this->invert($bUseMatch && $bMatch);
        }

        $value = $this->getQueryParameter($param);

        // Register param on TSFE service for cache and linkVars management
        FrontendControllerService::registerQueryParameter(
            $param,
            $value,
            !$this->use_session,
        );

        $values = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values'),
            true,
        );

        return $this->invert($this->storeInSession(
            (\count($values) === 0) || \in_array($value, $values, true),
        ));
    }

    /**
     * Get all query parameters from the current request.
     *
     * @return array<string, mixed> Query parameters
     */
    protected function getQueryParams(): array
    {
        // Try to get from PSR-7 request first (preferred in TYPO3 v12+)
        $request = Container::get()->getRequest();
        if ($request !== null) {
            return $request->getQueryParams();
        }

        // Fallback to GLOBALS['TYPO3_REQUEST'] if available
        $globalRequest = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($globalRequest instanceof \Psr\Http\Message\ServerRequestInterface) {
            return $globalRequest->getQueryParams();
        }

        // Ultimate fallback to $_GET for backward compatibility
        return $_GET;
    }

    /**
     * Get a query parameter value.
     *
     * @param string $param Parameter name
     *
     * @return mixed Parameter value or null if not set
     */
    protected function getQueryParameter(string $param): mixed
    {
        $queryParams = $this->getQueryParams();
        return $queryParams[$param] ?? null;
    }
}
