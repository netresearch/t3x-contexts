<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Context;

use ArrayObject;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\ContextException;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Loads contexts and provides access to them
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 *
 * @extends ArrayObject<int|string, AbstractContext>
 */
class Container extends ArrayObject
{
    /**
     */
    protected static ?Container $instance = null;

    /**
     * The current server request.
     */
    protected ?ServerRequestInterface $request = null;

    /**
     * Singleton accessor
     *
     */
    public static function get(): Container
    {
        if (static::$instance === null) {
            static::$instance = new self();
        }

        return static::$instance;
    }

    /**
     * Reset the singleton instance.
     * Useful for testing to ensure a fresh state between tests.
     */
    public static function reset(): void
    {
        static::$instance = null;
    }

    /**
     * Set the current server request.
     */
    public function setRequest(?ServerRequestInterface $request): Container
    {
        $this->request = $request;
        return $this;
    }

    /**
     * Get the current server request.
     */
    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * Loads all contexts and checks if they match
     *
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function initMatching(): Container
    {
        $this->setActive($this->match($this->loadAvailable()));
        return $this;
    }

    /**
     * Loads all contexts.
     *
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function initAll(): Container
    {
        $this->setActive($this->loadAvailable());
        return $this;
    }

    /**
     * Find context by uid or alias
     *
     * @param int|string $uidOrAlias
     *
     */
    public function find($uidOrAlias): ?AbstractContext
    {
        if (is_numeric($uidOrAlias) && isset($this[$uidOrAlias])) {
            return $this[$uidOrAlias];
        }

        /** @var AbstractContext $context */
        foreach ($this as $context) {
            if (
                ($context->getUid() === $uidOrAlias)
                || ($context->getAlias() === strtolower((string) $uidOrAlias))
            ) {
                return $context;
            }
        }

        return null;
    }

    /**
     * Make the given contexts active (available in this container)
     *
     * @param AbstractContext[] $arContexts Array of context objects
     *
     */
    protected function setActive(array $arContexts): Container
    {
        $this->exchangeArray($arContexts);
        return $this;
    }

    /**
     * Loads all available contexts from database and instantiates them
     * and checks if they match.
     *
     * @return AbstractContext[] Array of available Tx_Contexts_Context_Abstract objects,
     *                           key is their uid
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    protected function loadAvailable(): array
    {
        $factory = GeneralUtility::makeInstance(Factory::class);
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);

        $queryBuilder = $connectionPool
            ->getQueryBuilderForTable('tx_contexts_contexts');

        $arRows = $queryBuilder
            ->select('*')
            ->from('tx_contexts_contexts')
            ->executeQuery()
            ->fetchAllAssociative();

        $contexts = [];
        foreach ($arRows as $arRow) {
            $context = $factory->createFromDb($arRow);

            if ($context !== null) {
                $contexts[$arRow['uid']] = $context;
            }
        }

        return $contexts;
    }

    /**
     * Matches all context objects. Resolves dependencies.
     *
     * @param AbstractContext[] $arContexts Array of available context objects
     *
     * @return AbstractContext[] Array of matched AbstractContext objects, key is their uid
     */
    protected function match(array $arContexts): array
    {
        $matched = [];
        $notMatched = [];
        $arContextsHelper = $arContexts;

        $loops = 0;
        do {
            foreach (array_keys($arContexts) as $uid) {
                $context = $arContexts[$uid];

                if ($context->getDisabled()) {
                    continue;
                }

                // resolve dependencies
                $arDeps = $context->getDependencies($arContextsHelper);
                $unresolvedDeps = \count($arDeps);
                foreach ($arDeps as $depUid => $enabled) {
                    if ($enabled) {
                        if (isset($matched[$depUid])) {
                            $arDeps[$depUid] = (object) [
                                'context' => $matched[$depUid],
                                'matched' => true,
                            ];
                            $unresolvedDeps--;
                        } elseif (isset($notMatched[$depUid])) {
                            $arDeps[$depUid] = (object) [
                                'context' => $notMatched[$depUid],
                                'matched' => false,
                            ];
                            $unresolvedDeps--;
                        }
                    } else {
                        $arDeps[$depUid] = (object) [
                            'context' => $arContextsHelper[$depUid],
                            'matched' => 'disabled',
                        ];
                        $unresolvedDeps--;
                    }
                    // FIXME: what happens when dependency context is not
                    // available at all (e.g. deleted)?
                }
                if ($unresolvedDeps > 0) {
                    // not all dependencies available yet, so skip this
                    // one for now
                    continue;
                }

                if ($context->match($arDeps)) {
                    $matched[$uid] = $context;
                } else {
                    $notMatched[$uid] = $context;
                }

                unset($arContexts[$uid]);
            }
        } while (\count($arContexts) > 0 && ++$loops < 10);

        return $matched;
    }
}
