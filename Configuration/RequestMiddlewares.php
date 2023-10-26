<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Netresearch\Contexts\Middleware\ContainerInitialization;

return [
    'frontend' => [
        'netresearch/context/container-initialization' => [
            'target' => ContainerInitialization::class,
            'before' => [
                'typo3/cms-frontend/page-resolver',
            ],
        ],
    ],
];
