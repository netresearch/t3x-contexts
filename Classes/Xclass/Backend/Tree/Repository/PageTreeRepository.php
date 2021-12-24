<?php
declare(strict_types = 1);
namespace Netresearch\Contexts\Xclass\Backend\Tree\Repository;
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


/**
 * need the context fields in the page tree
 *
 * Class PageTreeRepository
 * @package Netresearch\Contexts\Xclass\Backend\Tree\Repository
 */
class PageTreeRepository extends \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository
{
    /**
     * @var array
     */
    protected $contextFiels = [
        'tx_contexts_enable',
        'tx_contexts_disable'
    ];

    /**
     * @param int $workspaceId the workspace ID to be checked for.
     * @param array $additionalFieldsToQuery an array with more fields that should be accessed.
     */
    public function __construct(int $workspaceId = 0, array $additionalFieldsToQuery = [])
    {
        $additionalFieldsToQuery = array_merge($this->contextFiels, $additionalFieldsToQuery);
        parent::__construct($workspaceId, $additionalFieldsToQuery);
    }

}
