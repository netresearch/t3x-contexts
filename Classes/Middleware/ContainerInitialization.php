<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Middleware;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Netresearch\Contexts\Context\Container;
use Netresearch\Contexts\ContextException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Class ContainerInitialization
 *
 * @author  Rico Sonntag <rico.sonntag@netresearch.de>
 * @license Netresearch https://www.netresearch.de
 * @link    https://www.netresearch.de
 */
class ContainerInitialization implements MiddlewareInterface
{
    /**
     * Initialize container matching and assure page is accessible after initialization.
     *
     *
     *
     * @throws ContextException
     * @throws DBALException
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Container::get()
            ->setRequest($request)
            ->initMatching();

        return $handler->handle($request);
    }
}
