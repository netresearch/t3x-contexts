<?php

declare(strict_types=1);

/*
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace Netresearch\Contexts\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Covers the TypoScript the site set contributes.
 *
 * The debug output used to be wrapped in `[{$contexts.debug} == 1]`. TypoScript
 * conditions are Symfony ExpressionLanguage since TYPO3 12 and do not resolve
 * `{$constant}`, so that condition failed to parse — on every request, in the
 * backend as well as the frontend, twice each, because the include tree is
 * walked per request. It never gated anything; it only produced log noise.
 *
 * Replacing it with a value-level `if` has to keep the gate working, which is
 * what the two rendering tests below establish: the marker appears with the
 * setting on and is absent with it off.
 *
 * Both assertions only mean something because renderWithDebug() flushes the
 * caches: site configuration, settings and resolved TypoScript are cached for
 * the lifetime of the shared test instance, so without the flush the second
 * test reads the first one's setting and reports a gate that does not work.
 */
final class SiteSetTypoScriptTest extends FunctionalTestCase
{
    private const DEBUG_MARKER = '<!-- Contexts Extension Debug Mode Active -->';

    private const BASE_URL = 'https://contexts.local/';

    protected array $testExtensionsToLoad = [
        'netresearch/contexts',
    ];

    protected array $coreExtensionsToLoad = [
        'frontend',
    ];

    #[Test]
    public function theSetCarriesNoLegacyConstantCondition(): void
    {
        $setup = (string) file_get_contents(
            \dirname(__DIR__, 2) . '/Configuration/Sets/Contexts/setup.typoscript',
        );

        // A condition line is `[...]` at the start of a line. The parser rejects
        // a constant inside one, so the set must not contain any.
        self::assertSame(
            0,
            preg_match('/^\[[^\]]*\{\$/m', $setup),
            'The set must not gate TypoScript with a constant inside a condition',
        );
    }

    #[Test]
    public function theDebugMarkerIsRenderedWhenTheSettingIsOn(): void
    {
        self::assertStringContainsString(self::DEBUG_MARKER, $this->renderWithDebug(true));
    }

    #[Test]
    public function theDebugMarkerIsAbsentWhenTheSettingIsOff(): void
    {
        self::assertStringNotContainsString(self::DEBUG_MARKER, $this->renderWithDebug(false));
    }

    private function renderWithDebug(bool $debug): string
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');

        $siteConfigPath = $this->instancePath . '/typo3conf/sites/contexts-local';
        GeneralUtility::mkdir_deep($siteConfigPath);
        file_put_contents(
            $siteConfigPath . '/config.yaml',
            Yaml::dump([
                'rootPageId' => 1,
                'base' => self::BASE_URL,
                // The set is what contributes the TypoScript under test.
                'dependencies' => ['netresearch/contexts'],
                'languages' => [[
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                ]],
            ], 99, 2),
        );
        file_put_contents(
            $siteConfigPath . '/settings.yaml',
            Yaml::dump(['contexts' => ['debug' => $debug]], 99, 2),
        );

        // The site configuration, its settings and the resolved TypoScript are
        // cached, and the cache outlives a single test in the shared instance —
        // without this flush the second test reads the first one's setting and
        // the gate appears not to work at all.
        $cacheManager = $this->get(CacheManager::class);
        self::assertInstanceOf(CacheManager::class, $cacheManager);
        $cacheManager->flushCaches();

        // Not setUpFrontendRootPage(): it writes a template with clear = 3, which
        // discards everything accumulated before it — the site set's TypoScript
        // among it. clear = 0 lets the set survive, and this template adds only
        // the page object the set's headerData needs to attach to.
        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update('pages', ['is_siteroot' => 1], ['uid' => 1]);
        $this->getConnectionPool()
            ->getConnectionForTable('sys_template')
            ->insert('sys_template', [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Page object only',
                'constants' => '',
                'config' => "page = PAGE\npage.typeNum = 0\n",
            ]);

        return (string) $this->executeFrontendSubRequest(
            (new InternalRequest(self::BASE_URL))->withPageId(1),
        )->getBody();
    }
}
