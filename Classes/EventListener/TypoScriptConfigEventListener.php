<?php

/*
 * Copyright (c) 2025-2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\EventListener;

use Netresearch\Contexts\Service\QueryParameterService;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNodeInterface;
use TYPO3\CMS\Frontend\Event\ModifyTypoScriptConfigEvent;

/**
 * Adds the GET parameters of all query parameter contexts to
 * "config.linkVars", so the active channel survives following a link.
 *
 * Replaces the SC_OPTIONS['tslib/class.tslib_fe.php']['configArrayPostProc']
 * hook that was removed in TYPO3 v13.0 (#102932).
 *
 * The event result is cached, therefore the parameter list is derived from the
 * configured contexts and never from the current request. Listing a parameter
 * that is absent from a request is a no-op: the core LinkVarsCalculator only
 * carries over parameters that are actually present.
 */
#[AsEventListener(
    identifier: 'contexts/typoscript-config-link-vars',
    event: ModifyTypoScriptConfigEvent::class,
)]
final readonly class TypoScriptConfigEventListener
{
    public function __construct(
        private QueryParameterService $queryParameterService,
    ) {
    }

    public function __invoke(ModifyTypoScriptConfigEvent $event): void
    {
        $linkVarNames = $this->queryParameterService->getLinkVarNames();

        if ($linkVarNames === []) {
            return;
        }

        $configTree = $event->getConfigTree();
        $linkVarsNode = $configTree->getChildByName('linkVars');

        if (!$linkVarsNode instanceof ChildNodeInterface) {
            $linkVarsNode = new ChildNode('linkVars');
            $configTree->addChild($linkVarsNode);
        }

        $linkVars = trim((string) $linkVarsNode->getValue(), " \t,");

        foreach ($linkVarNames as $name) {
            if ($this->containsLinkVar($linkVars, $name)) {
                continue;
            }

            $linkVars = $linkVars === '' ? $name : $linkVars . ',' . $name;
        }

        $linkVarsNode->setValue($linkVars);

        $event->setConfigTree($configTree);
    }

    /**
     * Whether the given parameter name is already listed in a "config.linkVars"
     * value.
     *
     * The value is not split on commas: a single entry may carry a value
     * restriction that contains commas, e.g. "L(1,2)".
     */
    private function containsLinkVar(string $linkVars, string $name): bool
    {
        return preg_match(
            '/(?:^|,)[ \t]*' . preg_quote($name, '/') . '[ \t]*(?:\(|,|$)/',
            $linkVars,
        ) === 1;
    }
}
