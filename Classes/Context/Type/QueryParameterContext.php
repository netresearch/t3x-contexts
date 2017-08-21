<?php
namespace Netresearch\Contexts\Context\Type;

/***************************************************************
*  Copyright notice
*
*  (c) 2013 Netresearch GmbH & Co. KG <typo3.org@netresearch.de>
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
use Netresearch\Contexts\Context\AbstractContext;
use Netresearch\Contexts\Service\FrontendControllerService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
     * @return bool True if the context is active, false if not
     * @throws \Exception
     */
    public function match(array $arDependencies = array())
    {
        $param = trim($this->getConfValue('field_name'));
        if ($param === '') {
            throw new \Exception(
                'Parameter name missing from GET Parameter'
                . ' context configuration'
            );
        }

        if (!array_key_exists($param, $_GET)) {
            //load from session if no param given
            list($bUseMatch, $bMatch) = $this->getMatchFromSession();
            return $this->invert($bUseMatch && $bMatch);
        }

        $value = GeneralUtility::_GET($param);

        // Register param on TSFE service for cache and linkVars management
        FrontendControllerService::registerQueryParameter(
            $param, $value, !(bool) $this->use_session
        );

        $values = GeneralUtility::trimExplode("\n", $this->getConfValue('field_values'), true);

        return $this->invert($this->storeInSession(
            !count($values) || in_array($value, $values, true)
        ));
    }
}
