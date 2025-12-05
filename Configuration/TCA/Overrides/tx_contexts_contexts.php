<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

use Netresearch\Contexts\Api\Configuration;
use Netresearch\Contexts\Context\Type\CombinationContext;
use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Context\Type\HttpHeaderContext;
use Netresearch\Contexts\Context\Type\IpContext;
use Netresearch\Contexts\Context\Type\QueryParameterContext;
use Netresearch\Contexts\Context\Type\SessionContext;

defined('TYPO3') || die('Access denied.');

/**
 * TCA override for tx_contexts_contexts table
 */
Configuration::registerContextType(
    'default',
    'Select a type',
    '',
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Empty.xml',
);

Configuration::registerContextType(
    'domain',
    'Domain',
    DomainContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Domain.xml',
);

Configuration::registerContextType(
    'getparam',
    'GET parameter',
    QueryParameterContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/GetParam.xml',
);

Configuration::registerContextType(
    'ip',
    'IP',
    IpContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Ip.xml',
);

Configuration::registerContextType(
    'httpheader',
    'HTTP header',
    HttpHeaderContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/HttpHeader.xml',
);

Configuration::registerContextType(
    'combination',
    'Logical context combination',
    CombinationContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Combination.xml',
);

Configuration::registerContextType(
    'session',
    'Session variable',
    SessionContext::class,
    'FILE:EXT:contexts/Configuration/FlexForms/ContextType/Session.xml',
);
