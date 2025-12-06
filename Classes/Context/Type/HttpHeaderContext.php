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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Matches on an HTTP header with a certain value
 *
 * @author  Michael Schams <schams.net>
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class HttpHeaderContext extends AbstractContext
{
    /**
     * Check if the context is active now.
     *
     * @param array $arDependencies Array of dependent context objects
     *
     * @return bool True if the context is active, false if not
     */
    public function match(array $arDependencies = []): bool
    {
        // Determine which HTTP header has been configured
        $configValue = $this->getConfValue('field_name');
        $httpHeaderName = strtolower(trim($configValue));

        // Check, if header exists in HTTP request
        foreach ($_SERVER as $header => $value) {
            if (strtolower((string) $header) === $httpHeaderName) {
                // header exists - check if any configured values match
                return $this->invert($this->storeInSession(
                    $this->matchValues($value),
                ));
            }
        }

        // HTTP header does not exist
        return $this->invert(false);
    }

    /**
     * Checks if the given value is one of the configured allowed values
     *
     * @param string $value Current parameter value
     *
     * @return bool True if the current parameter value is one of the
     *                 configured values
     */
    protected function matchValues(string $value): bool
    {
        $arValues = GeneralUtility::trimExplode(
            "\n",
            $this->getConfValue('field_values'),
            true,
        );

        // Empty value list, so we allow any value
        if (\count($arValues) === 1 && $arValues[0] === '') {
            return $value !== '';
        }

        $arValues = array_map(trim(...), $arValues);

        return \in_array($value, $arValues, true);
    }
}
