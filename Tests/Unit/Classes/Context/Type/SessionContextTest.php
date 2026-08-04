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

namespace Netresearch\Contexts\Tests\Unit\Context\Type;

use Netresearch\Contexts\Context\Type\SessionContext;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for SessionContext.
 *
 * SessionContext checks if a specific session variable is set in the frontend user session.
 *
 * Since TYPO3 v14 (#107831) the frontend user is read from the "frontend.user"
 * request attribute instead of the removed TypoScriptFrontendController, so all
 * fixtures below drive the context through a PSR-7 request.
 */
final class SessionContextTest extends UnitTestCase
{
    #[Test]
    public function matchReturnsFalseWithoutRequest(): void
    {
        $context = $this->createSessionContext('my_session_var', null);

        self::assertFalse($context->match(), 'Without a request, context should not match');
    }

    #[Test]
    public function matchReturnsFalseWhenFeUserIsNull(): void
    {
        // Create a context with a request that carries no "frontend.user"
        $context = $this->createSessionContextWithNullFeUser('my_session_var');

        self::assertFalse($context->match(), 'Without fe_user, context should not match');
    }

    #[Test]
    public function matchReturnsFalseWhenSessionVariableNotSet(): void
    {
        $context = $this->createSessionContext('my_session_var', null, true);

        self::assertFalse($context->match(), 'Non-existent session variable should not match');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsSet(): void
    {
        $context = $this->createSessionContext('my_session_var', 'some_value', true);

        self::assertTrue($context->match(), 'Existing session variable should match');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsEmptyString(): void
    {
        // Empty string is still "set" - only null means not set
        $context = $this->createSessionContext('my_session_var', '', true);

        self::assertTrue($context->match(), 'Empty string session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsZero(): void
    {
        $context = $this->createSessionContext('my_session_var', 0, true);

        self::assertTrue($context->match(), 'Zero value session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsFalse(): void
    {
        $context = $this->createSessionContext('my_session_var', false, true);

        self::assertTrue($context->match(), 'False value session variable should match (is set)');
    }

    #[Test]
    public function matchWithInvertReturnsTrueWhenSessionVariableNotSet(): void
    {
        $context = $this->createSessionContext('my_session_var', null, true, true);

        self::assertTrue($context->match(), 'With invert, non-existent session should match');
    }

    #[Test]
    public function matchWithInvertReturnsFalseWhenSessionVariableIsSet(): void
    {
        $context = $this->createSessionContext('my_session_var', 'value', true, true);

        self::assertFalse($context->match(), 'With invert, existing session should not match');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsArray(): void
    {
        $context = $this->createSessionContext('my_session_var', ['key' => 'value'], true);

        self::assertTrue($context->match(), 'Array value session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsObject(): void
    {
        $obj = new stdClass();
        $obj->property = 'value';

        $context = $this->createSessionContext('my_session_var', $obj, true);

        self::assertTrue($context->match(), 'Object value session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsNegativeNumber(): void
    {
        $context = $this->createSessionContext('my_session_var', -1, true);

        self::assertTrue($context->match(), 'Negative number session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsTrueWhenSessionVariableIsEmptyArray(): void
    {
        $context = $this->createSessionContext('my_session_var', [], true);

        self::assertTrue($context->match(), 'Empty array session variable should match (is set)');
    }

    #[Test]
    public function matchReturnsFalseWithInvertWhenSessionVariableIsEmptyString(): void
    {
        // Empty string is still "set" (not null), so with invert it should return false
        $context = $this->createSessionContext('my_session_var', '', true, true);

        self::assertFalse($context->match(), 'With invert, empty string session should not match');
    }

    #[Test]
    public function matchHandlesDifferentVariableNames(): void
    {
        $context1 = $this->createSessionContext('user_logged_in', true, true);
        $context2 = $this->createSessionContext('cart_items', 5, true);
        $context3 = $this->createSessionContext('preferences', ['theme' => 'dark'], true);

        self::assertTrue($context1->match(), 'Boolean true should match');
        self::assertTrue($context2->match(), 'Integer should match');
        self::assertTrue($context3->match(), 'Array should match');
    }

    /**
     * Create a SessionContext driven by a request that carries no frontend user.
     */
    private function createSessionContextWithNullFeUser(string $variableName): SessionContext
    {
        return $this->createContext(
            $variableName,
            new ServerRequest('https://example.com/'),
            false,
        );
    }

    /**
     * Create a SessionContext driven by a request carrying a frontend user with
     * the given session value.
     *
     * @param string $variableName  The session variable name to check
     * @param mixed  $sessionValue  The value to return from session (null = not set)
     * @param bool   $hasFeUser     Whether the request carries a frontend user
     * @param bool   $invert        Whether to invert the match result
     */
    private function createSessionContext(
        string $variableName,
        mixed $sessionValue,
        bool $hasFeUser = false,
        bool $invert = false,
    ): SessionContext {
        $request = null;

        if ($hasFeUser) {
            $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
            $mockFeUser->method('getKey')
                ->with('ses', $variableName)
                ->willReturn($sessionValue);

            // Exactly how the core FrontendUserAuthenticator middleware
            // publishes the frontend user since TYPO3 v13.
            $request = (new ServerRequest('https://example.com/'))
                ->withAttribute('frontend.user', $mockFeUser);
        }

        return $this->createContext($variableName, $request, $invert);
    }

    private function createContext(
        string $variableName,
        ?ServerRequestInterface $request,
        bool $invert,
    ): SessionContext {
        return new class ($variableName, $request, $invert) extends SessionContext {
            private readonly string $mockVariableName;

            private readonly ?ServerRequestInterface $mockRequest;

            private readonly bool $mockInvert;

            public function __construct(
                string $variableName,
                ?ServerRequestInterface $request,
                bool $invert,
            ) {
                $this->mockVariableName = $variableName;
                $this->mockRequest = $request;
                $this->mockInvert = $invert;
            }

            protected function getConfValue(
                string $fieldNameArg,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                if ($fieldNameArg === 'field_variable') {
                    return $this->mockVariableName;
                }

                return $default;
            }

            protected function getRequest(): ?ServerRequestInterface
            {
                return $this->mockRequest;
            }

            protected function invert(bool $bMatch): bool
            {
                return $this->mockInvert ? !$bMatch : $bMatch;
            }
        };
    }
}
