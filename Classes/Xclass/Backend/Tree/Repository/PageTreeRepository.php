<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Xclass\Backend\Tree\Repository;

/**
 * Need the context fields in the page tree
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class PageTreeRepository extends \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository
{
    /**
     */
    protected array $contextFields = [
        'tx_contexts_enable',
        'tx_contexts_disable',
    ];

    /**
     * @param int $workspaceId the workspace ID to be checked for.
     * @param array $additionalFieldsToQuery an array with more fields that should be accessed.
     */
    public function __construct(int $workspaceId = 0, array $additionalFieldsToQuery = [])
    {
        $additionalFieldsToQuery = array_merge($this->contextFields, $additionalFieldsToQuery);

        parent::__construct($workspaceId, $additionalFieldsToQuery);
    }
}
