<?php
namespace Netresearch\Contexts\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Netresearch\Contexts\Context\Container;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;


/**
 * Class ContainerInitialization
 * @package Netresearch\Contexts\Middleware
 */
class ContainerInitialization implements MiddlewareInterface
{

    const ACCESS_DENIED_CONTEXTS = 'access.context';

    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * ContainerInitialization constructor.
     * @param TypoScriptFrontendController|null $controller
     */
    public function __construct(TypoScriptFrontendController $controller = null)
    {
        $this->controller = $controller ?? $GLOBALS['TSFE'];
    }

    /**
     * initialize Container Matching
     * and assure page ist accessible after initialization
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        Container::get()->initMatching();
        return $handler->handle($request);
    }
}
