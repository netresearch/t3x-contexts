<?php

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
use stdClass;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Tests for SessionContext.
 *
 * SessionContext checks if a specific session variable is set in the frontend user session.
 */
final class SessionContextTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    #[Test]
    public function matchReturnsFalseWithoutFrontendController(): void
    {
        $context = $this->createSessionContext('my_session_var', null);

        self::assertFalse($context->match(), 'Without TSFE, context should not match');
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
     * Create a SessionContext with mocked TSFE and session.
     *
     * @param string $variableName The session variable name to check
     * @param mixed $sessionValue The value to return from session (null = not set)
     * @param bool $hasTsfe Whether to provide a mock TSFE
     * @param bool $invert Whether to invert the match result
     */
    private function createSessionContext(
        string $variableName,
        mixed $sessionValue,
        bool $hasTsfe = false,
        bool $invert = false,
    ): SessionContext {
        $mockFeUser = null;

        if ($hasTsfe) {
            $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
            $mockFeUser->method('getKey')
                ->with('ses', $variableName)
                ->willReturn($sessionValue);
        }

        return new class ($variableName, $mockFeUser, $hasTsfe, $invert) extends SessionContext {
            private readonly string $mockVariableName;

            private readonly ?FrontendUserAuthentication $mockFeUser;

            private readonly bool $hasTsfe;

            private readonly bool $mockInvert;

            public function __construct(
                string $variableName,
                ?FrontendUserAuthentication $feUser,
                bool $hasTsfe,
                bool $invert,
            ) {
                $this->mockVariableName = $variableName;
                $this->mockFeUser = $feUser;
                $this->hasTsfe = $hasTsfe;
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

            protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
            {
                if (!$this->hasTsfe) {
                    return null;
                }

                // Create a stub TSFE with fe_user property properly declared
                // Note: Cannot use native type - TYPO3 12.4 has untyped $fe_user property
                return new class ($this->mockFeUser) extends TypoScriptFrontendController {
                    /** @var FrontendUserAuthentication */
                    public $fe_user;

                    public function __construct(FrontendUserAuthentication $feUser)
                    {
                        $this->fe_user = $feUser;
                    }
                };
            }

            protected function invert(bool $bMatch): bool
            {
                return $this->mockInvert ? !$bMatch : $bMatch;
            }
        };
    }
}
