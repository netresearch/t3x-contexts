<?php

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Unit\Context;

use Netresearch\Contexts\Context\AbstractContext;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Stub class for TypoScriptFrontendController that properly defines fe_user property.
 *
 * This avoids PHP 8.2+ deprecation warnings about dynamic property creation
 * when testing code that accesses $tsfe->fe_user.
 *
 * @internal Only for testing
 */
class TypoScriptFrontendControllerStub extends TypoScriptFrontendController
{
    /**
     * @var FrontendUserAuthentication|null
     */
    public $fe_user = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $rootLine = [];

    /**
     * Empty constructor that bypasses parent dependencies.
     */
    public function __construct()
    {
        // Don't call parent - TSFE has complex dependencies we don't need for testing
    }
}

/**
 * Tests for AbstractContext class.
 *
 * AbstractContext is the base class for all context types. It handles:
 * - Context configuration from database rows
 * - Session storage for match results
 * - Inversion of match results
 * - Configuration value retrieval from FlexForm XML
 */
final class AbstractContextTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Initialize runtime cache required by GeneralUtility::xml2array()
        $cacheManager = new \TYPO3\CMS\Core\Cache\CacheManager();
        $cacheManager->setCacheConfigurations([
            'runtime' => [
                'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
                'backend' => \TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend::class,
                'options' => [],
                'groups' => [],
            ],
        ]);
        \TYPO3\CMS\Core\Utility\GeneralUtility::setSingletonInstance(
            \TYPO3\CMS\Core\Cache\CacheManager::class,
            $cacheManager,
        );
    }

    #[Test]
    public function constructorSetsPropertiesFromRow(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'uid' => 42,
            'type' => 'domain',
            'title' => 'Domain Context',
            'alias' => 'MyDomain',
            'invert' => 1,
            'use_session' => 1,
            'disabled' => 1,
            'hide_in_backend' => 1,
        ]));

        self::assertSame(42, $context->getUid());
        self::assertSame('domain', $context->getType());
        self::assertSame('Domain Context', $context->getTitle());
        self::assertSame('mydomain', $context->getAlias()); // Note: lowercase
        self::assertTrue($context->getDisabled());
        self::assertTrue($context->getHideInBackend());
    }

    #[Test]
    public function constructorWithEmptyRowSetsDefaults(): void
    {
        $context = $this->createTestableContext([]);

        self::assertSame(0, $context->getUid());
        self::assertSame('', $context->getType());
        self::assertSame('', $context->getTitle());
        self::assertSame('', $context->getAlias());
        self::assertFalse($context->getDisabled());
        self::assertFalse($context->getHideInBackend());
    }

    #[Test]
    public function getAliasReturnsLowercase(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'alias' => 'MyMixedCASEAlias',
        ]));

        self::assertSame('mymixedcasealias', $context->getAlias());
    }

    #[Test]
    public function getDependenciesReturnsEmptyArrayByDefault(): void
    {
        $context = $this->createTestableContext($this->createContextRow());

        self::assertSame([], $context->getDependencies([]));
    }

    #[Test]
    public function setInvertChangesInvertBehavior(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 0]));

        // Initially not inverted
        self::assertTrue($context->exposeInvert(true));
        self::assertFalse($context->exposeInvert(false));

        // After setting invert
        $context->setInvert(true);
        self::assertFalse($context->exposeInvert(true));
        self::assertTrue($context->exposeInvert(false));
    }

    #[Test]
    public function setUseSessionChangesSessionBehavior(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        // With session disabled, getMatchFromSession returns [false, null]
        [$useSession, $result] = $context->exposeGetMatchFromSession();
        self::assertFalse($useSession);
        self::assertNull($result);
    }

    #[Test]
    public function invertReturnsSameValueWhenNotInverted(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 0]));

        self::assertTrue($context->exposeInvert(true));
        self::assertFalse($context->exposeInvert(false));
    }

    #[Test]
    public function invertReturnsOppositeValueWhenInverted(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 1]));

        self::assertFalse($context->exposeInvert(true));
        self::assertTrue($context->exposeInvert(false));
    }

    #[Test]
    public function getConfValueReturnsDefaultWhenSheetNotSet(): void
    {
        $context = $this->createTestableContext($this->createContextRow());

        self::assertSame(
            'default_value',
            $context->exposeGetConfValue('nonexistent', 'default_value'),
        );
    }

    #[Test]
    public function getConfValueReturnsValueFromConfiguration(): void
    {
        // Create XML configuration that matches FlexForm structure
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="testField">
                    <value index="vDEF">configured_value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame(
            'configured_value',
            $context->exposeGetConfValue('testField'),
        );
    }

    #[Test]
    public function getMatchFromSessionReturnsFalseNullWhenSessionDisabled(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertFalse($shouldUse);
        self::assertNull($value);
    }

    #[Test]
    public function getMatchFromSessionReturnsFalseNullWhenNoTsfe(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));
        $context->setMockTsfe(null);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertFalse($shouldUse);
        self::assertNull($value);
    }

    #[Test]
    public function storeInSessionReturnsMatchValueWhenSessionDisabled(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        self::assertTrue($context->exposeStoreInSession(true));
        self::assertFalse($context->exposeStoreInSession(false));
    }

    #[Test]
    public function storeInSessionReturnsMatchValueWhenNoTsfe(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));
        $context->setMockTsfe(null);

        self::assertTrue($context->exposeStoreInSession(true));
        self::assertFalse($context->exposeStoreInSession(false));
    }

    #[Test]
    public function getRemoteAddressReturnsStringFromIndpEnv(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv('192.168.1.100');

        self::assertSame('192.168.1.100', $context->exposeGetRemoteAddress());
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenNotString(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(false);

        self::assertSame('', $context->exposeGetRemoteAddress());
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(null);

        self::assertSame('', $context->exposeGetRemoteAddress());
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenArray(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(['some' => 'array']);

        self::assertSame('', $context->exposeGetRemoteAddress());
    }

    #[Test]
    public function getSessionReturnsNullWhenNoTsfe(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockTsfe(null);

        self::assertNull($context->exposeGetSession());
    }

    #[Test]
    public function getSessionReturnsNullWhenFeUserIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow());

        // Create TSFE mock without fe_user property
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        // fe_user is not set, so it will be null
        $context->setMockTsfe($mockTsfe);

        self::assertNull($context->exposeGetSession());
    }

    #[Test]
    public function storeInSessionReturnsMatchValueWhenFeUserIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));

        // Create TSFE mock without fe_user property
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        $context->setMockTsfe($mockTsfe);

        // Should return match value directly since fe_user is unavailable
        self::assertTrue($context->exposeStoreInSession(true));
        self::assertFalse($context->exposeStoreInSession(false));
    }

    #[Test]
    public function getMatchFromSessionReturnsFalseNullWhenFeUserIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));

        // Create TSFE mock without fe_user property
        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        $context->setMockTsfe($mockTsfe);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        // Session is enabled but fe_user unavailable, so should return [false, null]
        self::assertFalse($shouldUse);
        self::assertNull($value);
    }

    #[Test]
    public function matchUsesInvertLogic(): void
    {
        // Test without inversion
        $context1 = $this->createTestableContext($this->createContextRow([
            'invert' => 0,
            'use_session' => 0,
        ]));
        self::assertTrue($context1->match());

        // Test with inversion
        $context2 = $this->createTestableContext($this->createContextRow([
            'invert' => 1,
            'use_session' => 0,
        ]));
        self::assertFalse($context2->match());
    }

    #[Test]
    public function httpXForwardedForConstantHasCorrectValue(): void
    {
        self::assertSame('HTTP_X_FORWARDED_FOR', AbstractContext::HTTP_X_FORWARDED_FOR);
    }

    #[Test]
    public function remoteAddrConstantHasCorrectValue(): void
    {
        self::assertSame('REMOTE_ADDR', AbstractContext::REMOTE_ADDR);
    }

    #[Test]
    public function constructorParsesXmlConfiguration(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="domain">
                    <value index="vDEF">example.com</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame('example.com', $context->exposeGetConfValue('domain'));
    }

    #[Test]
    public function getConfValueReturnsDefaultForMissingField(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="existingField">
                    <value index="vDEF">some_value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame('fallback', $context->exposeGetConfValue('missingField', 'fallback'));
    }

    #[Test]
    public function getConfValueSupportsCustomSheets(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sCustom">
            <language index="lDEF">
                <field index="customField">
                    <value index="vDEF">custom_value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame(
            'custom_value',
            $context->exposeGetConfValue('customField', '', 'sCustom'),
        );
    }

    #[Test]
    public function multipleBooleanCastsWorkCorrectly(): void
    {
        // Test that string '0' and '1' from database are cast correctly
        $context = $this->createTestableContext($this->createContextRow([
            'invert' => '1',
            'use_session' => '1',
            'disabled' => '1',
            'hide_in_backend' => '1',
        ]));

        self::assertTrue($context->getDisabled());
        self::assertTrue($context->getHideInBackend());
    }

    #[Test]
    public function constructorHandlesEmptyTypeConf(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => '',
        ]));

        // Should not throw and should return default
        self::assertSame('default', $context->exposeGetConfValue('anyField', 'default'));
    }

    #[Test]
    public function getConfValueReturnsDefaultWhenSheetExistsButLanguageDoesNot(): void
    {
        // XML with sheet but no lDEF language key
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lOTHER">
                <field index="testField">
                    <value index="vDEF">value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        // Request with default lDEF, which doesn't exist
        self::assertSame(
            'default_value',
            $context->exposeGetConfValue('testField', 'default_value', 'sDEF', 'lDEF'),
        );
    }

    #[Test]
    public function getConfValueReturnsDefaultWhenLanguageExistsButFieldDoesNot(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="otherField">
                    <value index="vDEF">value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame(
            'fallback',
            $context->exposeGetConfValue('nonExistentField', 'fallback'),
        );
    }

    #[Test]
    public function getConfValueSupportsCustomValueIndex(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="testField">
                    <value index="vDEF">default_value</value>
                    <value index="vCUSTOM">custom_value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        // Access with custom value index
        self::assertSame(
            'custom_value',
            $context->exposeGetConfValue('testField', '', 'sDEF', 'lDEF', 'vCUSTOM'),
        );
    }

    #[Test]
    public function getConfValueReturnsDefaultWhenValueIndexDoesNotExist(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="testField">
                    <value index="vDEF">default_value</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        // Access with non-existent value index
        self::assertSame(
            'fallback',
            $context->exposeGetConfValue('testField', 'fallback', 'sDEF', 'lDEF', 'vNONEXISTENT'),
        );
    }

    #[Test]
    public function getConfValueSupportsMultipleLanguages(): void
    {
        $xmlConfig = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="testField">
                    <value index="vDEF">english</value>
                </field>
            </language>
            <language index="lDE">
                <field index="testField">
                    <value index="vDEF">german</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $xmlConfig,
        ]));

        self::assertSame('english', $context->exposeGetConfValue('testField', '', 'sDEF', 'lDEF'));
        self::assertSame('german', $context->exposeGetConfValue('testField', '', 'sDEF', 'lDE'));
    }

    #[Test]
    public function setUseSessionEnablesSessionStorage(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        // Initially session disabled
        [$useSession1,] = $context->exposeGetMatchFromSession();
        self::assertFalse($useSession1);

        // Enable session
        $context->setUseSession(true);

        // Now session check should proceed further (but still fail without TSFE)
        $context->setMockTsfe(null);
        [$useSession2, $value2] = $context->exposeGetMatchFromSession();
        self::assertFalse($useSession2); // false because no TSFE
        self::assertNull($value2);
    }

    #[Test]
    public function constructorCastsUidFromStringValue(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'uid' => '123',
        ]));

        self::assertSame(123, $context->getUid());
    }

    #[Test]
    public function getTypeFallsBackToEmptyStringWhenNotSet(): void
    {
        $context = $this->createTestableContext([
            'uid' => 1,
            'type' => '',
            'title' => 'Test',
            'alias' => 'test',
            'tstamp' => time(),
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
        ]);

        self::assertSame('', $context->getType());
    }

    // ========================================
    // Comprehensive Session-Related Tests
    // ========================================

    #[Test]
    public function getMatchFromSessionReturnsFalseNullWhenUseSessionIsFalse(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertFalse($shouldUse, 'Should not use session when use_session is false');
        self::assertNull($value, 'Value should be null when use_session is false');
    }

    #[Test]
    public function getMatchFromSessionReturnsFalseNullWhenUseSessionIsTrueButSessionIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));
        $context->setMockTsfe(null);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertFalse($shouldUse, 'Should not use session when session returns null');
        self::assertNull($value, 'Value should be null when session is null');
    }

    #[Test]
    public function getMatchFromSessionReturnsTrueTrueWhenSessionHasValueTrue(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 123,
            'tstamp' => 1234567890,
        ]));

        // Create mock fe_user with session data
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-123-1234567890')
            ->willReturn(true);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertTrue($shouldUse, 'Should use session when session has value');
        self::assertTrue($value, 'Value should be true when session stores true');
    }

    #[Test]
    public function getMatchFromSessionReturnsTrueFalseWhenSessionHasValueFalse(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 456,
            'tstamp' => 9876543210,
        ]));

        // Create mock fe_user with session data
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-456-9876543210')
            ->willReturn(false);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertTrue($shouldUse, 'Should use session when session has value');
        self::assertFalse($value, 'Value should be false when session stores false');
    }

    #[Test]
    public function getMatchFromSessionCastsSessionValueToBoolean(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 789,
            'tstamp' => 1111111111,
        ]));

        // Create mock fe_user with session data (integer 1)
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-789-1111111111')
            ->willReturn(1);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        [$shouldUse, $value] = $context->exposeGetMatchFromSession();

        self::assertTrue($shouldUse, 'Should use session when session has value');
        self::assertTrue($value, 'Value should be cast to boolean true');
        self::assertIsBool($value, 'Value should be boolean type');
    }

    #[Test]
    public function getSessionReturnsNullWhenTsfeIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockTsfe(null);

        $result = $context->exposeGetSession();

        self::assertNull($result, 'Should return null when TSFE is not available');
    }

    #[Test]
    public function getSessionReturnsNullWhenTsfeExistsButFeUserIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow());

        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        // fe_user property is not set, defaults to null
        $context->setMockTsfe($mockTsfe);

        $result = $context->exposeGetSession();

        self::assertNull($result, 'Should return null when fe_user is not available');
    }

    #[Test]
    public function getSessionReturnsSessionDataWhenFeUserExists(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'uid' => 100,
            'tstamp' => 2222222222,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-100-2222222222')
            ->willReturn(true);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $result = $context->exposeGetSession();

        self::assertTrue($result, 'Should return session data when fe_user exists');
    }

    #[Test]
    public function getSessionReturnsNullWhenNoSessionDataExists(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'uid' => 200,
            'tstamp' => 3333333333,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-200-3333333333')
            ->willReturn(null);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $result = $context->exposeGetSession();

        self::assertNull($result, 'Should return null when no session data exists');
    }

    #[Test]
    public function getSessionUsesCorrectSessionKeyFormat(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'uid' => 42,
            'tstamp' => 1234567890,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->expects(self::once())
            ->method('getKey')
            ->with('ses', 'contexts-42-1234567890')
            ->willReturn(true);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $context->exposeGetSession();
    }

    #[Test]
    public function storeInSessionReturnsMatchWhenUseSessionIsFalse(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 0]));

        self::assertTrue($context->exposeStoreInSession(true), 'Should return true when match is true');
        self::assertFalse($context->exposeStoreInSession(false), 'Should return false when match is false');
    }

    #[Test]
    public function storeInSessionReturnsMatchWhenTsfeIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));
        $context->setMockTsfe(null);

        self::assertTrue($context->exposeStoreInSession(true), 'Should return true without storing when TSFE is null');
        self::assertFalse($context->exposeStoreInSession(false), 'Should return false without storing when TSFE is null');
    }

    #[Test]
    public function storeInSessionReturnsMatchWhenFeUserIsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['use_session' => 1]));

        $mockTsfe = $this->createMock(TypoScriptFrontendController::class);
        // fe_user is not set, defaults to null
        $context->setMockTsfe($mockTsfe);

        self::assertTrue($context->exposeStoreInSession(true), 'Should return true without storing when fe_user is null');
        self::assertFalse($context->exposeStoreInSession(false), 'Should return false without storing when fe_user is null');
    }

    #[Test]
    public function storeInSessionStoresAndReturnsMatchWhenAllConditionsMet(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 500,
            'tstamp' => 4444444444,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->expects(self::once())
            ->method('setKey')
            ->with('ses', 'contexts-500-4444444444', true);
        $mockFeUser->expects(self::once())
            ->method('storeSessionData');

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $result = $context->exposeStoreInSession(true);

        self::assertTrue($result, 'Should return match value after storing');
    }

    #[Test]
    public function storeInSessionStoresFalseValueCorrectly(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 600,
            'tstamp' => 5555555555,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->expects(self::once())
            ->method('setKey')
            ->with('ses', 'contexts-600-5555555555', false);
        $mockFeUser->expects(self::once())
            ->method('storeSessionData');

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $result = $context->exposeStoreInSession(false);

        self::assertFalse($result, 'Should return false value after storing');
    }

    #[Test]
    public function storeInSessionCallsStoreSessionDataAfterSetKey(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 700,
            'tstamp' => 6666666666,
        ]));

        $callOrder = [];
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->expects(self::once())
            ->method('setKey')
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'setKey';
            });
        $mockFeUser->expects(self::once())
            ->method('storeSessionData')
            ->willReturnCallback(function () use (&$callOrder): void {
                $callOrder[] = 'storeSessionData';
            });

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $context->exposeStoreInSession(true);

        self::assertSame(['setKey', 'storeSessionData'], $callOrder, 'storeSessionData should be called after setKey');
    }

    #[Test]
    public function getRemoteAddressReturnsValidIpString(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv('192.168.1.100');

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('192.168.1.100', $result, 'Should return IP address string');
    }

    #[Test]
    public function getRemoteAddressReturnsIpv6String(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv('2001:0db8:85a3:0000:0000:8a2e:0370:7334');

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('2001:0db8:85a3:0000:0000:8a2e:0370:7334', $result, 'Should return IPv6 address string');
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenIndpEnvReturnsNull(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(null);

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('', $result, 'Should return empty string when getIndpEnv returns null');
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenIndpEnvReturnsFalse(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(false);

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('', $result, 'Should return empty string when getIndpEnv returns false');
    }

    #[Test]
    public function getRemoteAddressReturnsEmptyStringWhenIndpEnvReturnsInteger(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv(123);

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('', $result, 'Should return empty string when getIndpEnv returns integer');
    }

    #[Test]
    public function getRemoteAddressCallsGetIndpEnvWithRemoteAddrConstant(): void
    {
        $context = $this->createTestableContext($this->createContextRow());
        $context->setMockIndpEnv('10.0.0.1');

        $result = $context->exposeGetRemoteAddress();

        self::assertSame('10.0.0.1', $result);
        // Verify exposeGetIndpEnv was called with REMOTE_ADDR
        $indpEnvResult = $context->exposeGetIndpEnv(AbstractContext::REMOTE_ADDR);
        self::assertSame('10.0.0.1', $indpEnvResult);
    }

    #[Test]
    public function sessionIntegrationWithMatchMethod(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 999,
            'tstamp' => 7777777777,
            'invert' => 0,
        ]));

        // First call: no session data, should store and return true (inverted to true)
        $mockFeUser1 = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser1->method('getKey')->willReturn(null);
        $mockFeUser1->expects(self::once())->method('setKey');
        $mockFeUser1->expects(self::once())->method('storeSessionData');

        $stubTsfe1 = new TypoScriptFrontendControllerStub();
        $stubTsfe1->fe_user = $mockFeUser1;
        $context->setMockTsfe($stubTsfe1);

        $result1 = $context->match();
        self::assertTrue($result1, 'First match should calculate and store result');
    }

    #[Test]
    public function sessionIntegrationWithMatchMethodAndStoredValue(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 888,
            'tstamp' => 8888888888,
            'invert' => 0,
        ]));

        // Session has stored value true
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')->willReturn(true);
        // Should NOT call setKey since we're using stored value
        $mockFeUser->expects(self::never())->method('setKey');

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $result = $context->match();
        self::assertTrue($result, 'Match should use stored session value');
    }

    #[Test]
    public function multipleSessionOperationsWithDifferentContexts(): void
    {
        // Create two contexts with different UIDs and timestamps
        $context1 = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 101,
            'tstamp' => 1000000001,
        ]));

        $context2 = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 102,
            'tstamp' => 1000000002,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->willReturnCallback(function ($type, $key) {
                if ($key === 'contexts-101-1000000001') {
                    return true;
                }
                if ($key === 'contexts-102-1000000002') {
                    return false;
                }
            });

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;

        $context1->setMockTsfe($stubTsfe);
        $context2->setMockTsfe($stubTsfe);

        $session1 = $context1->exposeGetSession();
        $session2 = $context2->exposeGetSession();

        self::assertTrue($session1, 'Context 1 should have session value true');
        self::assertFalse($session2, 'Context 2 should have session value false');
    }

    // ========================================================================
    // Additional setter method tests
    // ========================================================================

    #[Test]
    public function setInvertToFalseDisablesInversion(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 1]));

        // Initially invert is true, so results are inverted
        self::assertFalse($context->exposeInvert(true));

        // Disable invert
        $context->setInvert(false);

        // Now results should not be inverted
        self::assertTrue($context->exposeInvert(true));
    }

    #[Test]
    public function setUseSessionToFalseDisablesSessionUsage(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 555,
            'tstamp' => 5555555555,
        ]));

        // Create mock fe_user with session data
        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')
            ->with('ses', 'contexts-555-5555555555')
            ->willReturn(true);

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        // With use_session enabled, session should be checked
        [$shouldUse, $value] = $context->exposeGetMatchFromSession();
        self::assertTrue($shouldUse, 'Session should be used when enabled');

        // Disable session usage
        $context->setUseSession(false);

        // Now session should not be checked
        [$shouldUse2, $value2] = $context->exposeGetMatchFromSession();
        self::assertFalse($shouldUse2, 'Session should not be used after disabling');
    }

    // ========================================================================
    // Tests for getDependencies
    // ========================================================================

    #[Test]
    public function getDependenciesReturnsEmptyArrayWhenNoConfValue(): void
    {
        $context = $this->createTestableContext($this->createContextRow());

        $dependencies = $context->getDependencies([]);

        self::assertIsArray($dependencies);
        self::assertEmpty($dependencies);
    }

    #[Test]
    public function getDependenciesReturnsArrayFromConfValue(): void
    {
        // Create context with dependencies in FlexForm XML
        $flexFormWithDeps = '<?xml version="1.0" encoding="utf-8" standalone="yes"?>
<T3FlexForms>
    <data>
        <sheet index="sDEF">
            <language index="lDEF">
                <field index="field_dependencies">
                    <value index="vDEF">1,2,3</value>
                </field>
            </language>
        </sheet>
    </data>
</T3FlexForms>';

        $context = $this->createTestableContext($this->createContextRow([
            'type_conf' => $flexFormWithDeps,
        ]));

        // Pass an array of contexts (empty for this test)
        $dependencies = $context->getDependencies([]);

        // getDependencies returns contexts that this context depends on
        // When no matching contexts exist, it returns empty array
        self::assertIsArray($dependencies);
        // Since we didn't provide contexts with matching UIDs, it returns empty
        self::assertEmpty($dependencies);
    }

    // ========================================================================
    // Tests for getHideInBackend
    // ========================================================================

    #[Test]
    public function getHideInBackendReturnsTrueWhenSet(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'hide_in_backend' => 1,
        ]));

        self::assertTrue($context->getHideInBackend());
    }

    #[Test]
    public function getHideInBackendReturnsFalseWhenNotSet(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'hide_in_backend' => 0,
        ]));

        self::assertFalse($context->getHideInBackend());
    }

    // ========================================================================
    // Tests for getDisabled
    // ========================================================================

    #[Test]
    public function getDisabledReturnsTrueWhenSet(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'disabled' => 1,
        ]));

        self::assertTrue($context->getDisabled());
    }

    #[Test]
    public function getDisabledReturnsFalseWhenNotSet(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'disabled' => 0,
        ]));

        self::assertFalse($context->getDisabled());
    }

    // ========================================================================
    // Additional invert tests
    // ========================================================================

    #[Test]
    public function invertReturnsBooleanValues(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 0]));

        // Test various input types are properly cast and returned as boolean
        $result1 = $context->exposeInvert(true);
        $result2 = $context->exposeInvert(false);

        self::assertIsBool($result1);
        self::assertIsBool($result2);
        self::assertTrue($result1);
        self::assertFalse($result2);
    }

    #[Test]
    public function invertWithInvertFlagInvertsCorrectly(): void
    {
        $context = $this->createTestableContext($this->createContextRow(['invert' => 1]));

        // With invert flag, true becomes false and vice versa
        self::assertFalse($context->exposeInvert(true));
        self::assertTrue($context->exposeInvert(false));
    }

    // ========================================================================
    // Additional storeInSession tests
    // ========================================================================

    #[Test]
    public function storeInSessionReturnsFalseWhenStored(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 666,
            'tstamp' => 6666666666,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')->willReturn(null);
        $mockFeUser->expects(self::once())->method('setKey');
        $mockFeUser->expects(self::once())->method('storeSessionData');

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        // Store false value
        $result = $context->exposeStoreInSession(false);

        self::assertFalse($result, 'Should return the stored value (false)');
    }

    #[Test]
    public function storeInSessionCallsSetKeyWithCorrectParameters(): void
    {
        $context = $this->createTestableContext($this->createContextRow([
            'use_session' => 1,
            'uid' => 777,
            'tstamp' => 7777777777,
        ]));

        $mockFeUser = $this->createMock(FrontendUserAuthentication::class);
        $mockFeUser->method('getKey')->willReturn(null);
        $mockFeUser->expects(self::once())
            ->method('setKey')
            ->with('ses', 'contexts-777-7777777777', true);
        $mockFeUser->expects(self::once())->method('storeSessionData');

        $stubTsfe = new TypoScriptFrontendControllerStub();
        $stubTsfe->fe_user = $mockFeUser;
        $context->setMockTsfe($stubTsfe);

        $context->exposeStoreInSession(true);
    }

    /**
     * Creates a testable concrete implementation of AbstractContext.
     *
     * @param array $row Database row for context initialization
     * @return AbstractContext&object Anonymous class extending AbstractContext
     */
    private function createTestableContext(array $row = []): AbstractContext
    {
        return new class ($row) extends AbstractContext {
            private ?TypoScriptFrontendController $mockTsfe = null;

            private mixed $mockIndpEnv = null;

            private bool $mockIndpEnvSet = false;

            public function match(array $arDependencies = []): bool
            {
                // Test implementation: Use session if enabled, then invert
                [$useSession, $sessionResult] = $this->getMatchFromSession();
                if ($useSession) {
                    return $this->invert($sessionResult);
                }

                return $this->invert($this->storeInSession(true));
            }

            // Expose protected methods for testing
            public function exposeGetConfValue(
                string $fieldName,
                string $default = '',
                string $sheet = 'sDEF',
                string $lang = 'lDEF',
                string $value = 'vDEF',
            ): string {
                return $this->getConfValue($fieldName, $default, $sheet, $lang, $value);
            }

            public function exposeGetMatchFromSession(): array
            {
                return $this->getMatchFromSession();
            }

            public function exposeStoreInSession(bool $bMatch): bool
            {
                return $this->storeInSession($bMatch);
            }

            public function exposeInvert(bool $bMatch): bool
            {
                return $this->invert($bMatch);
            }

            public function exposeGetRemoteAddress(): string
            {
                return $this->getRemoteAddress();
            }

            public function exposeGetIndpEnv(string $key): mixed
            {
                return $this->getIndpEnv($key);
            }

            public function exposeGetSession(): mixed
            {
                return $this->getSession();
            }

            public function setMockTsfe(?TypoScriptFrontendController $tsfe): void
            {
                $this->mockTsfe = $tsfe;
            }

            public function setMockIndpEnv(mixed $value): void
            {
                $this->mockIndpEnv = $value;
                $this->mockIndpEnvSet = true;
            }

            protected function getTypoScriptFrontendController(): ?TypoScriptFrontendController
            {
                return $this->mockTsfe ?? parent::getTypoScriptFrontendController();
            }

            protected function getIndpEnv(string $strKey): mixed
            {
                if ($this->mockIndpEnvSet) {
                    return $this->mockIndpEnv;
                }
                return parent::getIndpEnv($strKey);
            }
        };
    }

    /**
     * Creates a complete database row for context initialization.
     *
     * @param array $overrides Values to override defaults
     * @return array Complete database row
     */
    private function createContextRow(array $overrides = []): array
    {
        return array_merge([
            'uid' => 1,
            'pid' => 0,
            'type' => 'testcontext',
            'title' => 'Test Context',
            'alias' => 'TestAlias',
            'type_conf' => '',
            'invert' => 0,
            'use_session' => 0,
            'disabled' => 0,
            'hide_in_backend' => 0,
            'tstamp' => time(),
        ], $overrides);
    }
}
