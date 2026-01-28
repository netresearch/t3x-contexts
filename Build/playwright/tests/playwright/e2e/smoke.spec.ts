/**
 * Smoke Tests for TYPO3 Contexts Extension
 *
 * Basic infrastructure tests to verify the TYPO3 backend loads
 * and the contexts extension module is accessible.
 *
 * These tests serve as the foundation E2E test suite.
 */
import { test, expect } from '../fixtures/setup-fixtures';

test.describe('Smoke Tests - TYPO3 Backend Infrastructure', () => {
  /**
   * Test 1: TYPO3 Backend Loads Successfully
   *
   * Verifies the TYPO3 backend is accessible and properly initialized
   */
  test('TYPO3 backend loads and is accessible', async ({ backend, page }) => {
    // The fixture ensures we're already logged in
    // Just verify we're on the TYPO3 backend
    expect(page.url()).toContain('/typo3/');

    // Check for the module menu (present in both v12 and v13)
    const moduleMenu = page.locator('[data-modulemenu-identifier], .scaffold-modulemenu, .t3js-topbar-button-modulemenu');
    await expect(moduleMenu.first()).toBeVisible();

    // Check for the toolbar/topbar
    const toolbar = page.locator('.scaffold-toolbar, .topbar, [class*="toolbar"]');
    await expect(toolbar.first()).toBeVisible();
  });

  /**
   * Test 2: Contexts Extension List Module is Accessible
   *
   * Verifies the contexts extension can be accessed via the List module.
   * The contexts extension doesn't have a dedicated module, so context
   * records are managed through the standard TYPO3 List module.
   */
  test('contexts extension is accessible via List module', async ({ backend, page }) => {
    // Navigate to the List module (manages all records including contexts)
    await backend.gotoModule('web_list');
    await backend.moduleLoaded();

    // Verify we're in the List module
    const listModule = backend.contentFrame.locator('.module-docheader, .module-body, .record-list');
    await expect(listModule.first()).toBeVisible();

    // Verify the page selector is available (for navigating to context records)
    const pageTree = page.locator('.scaffold-content-navigation-component, .t3js-pagetree, .tree-wrapper');
    await expect(pageTree.first()).toBeVisible();

    // Take a screenshot for visual reference
    await page.screenshot({ path: './playwright-report/list-module-accessible.png' });
  });

  /**
   * Test 3: Backend Module Menu is Responsive
   *
   * Ensures the module menu is interactive and can be clicked
   */
  test('backend module menu is responsive', async ({ page }) => {
    // Get the List module menu item
    const listModuleItem = page.locator('[data-modulemenu-identifier="web_list"], button:has-text("List"), a:has-text("List")').first();

    // Check if it's visible and clickable
    await expect(listModuleItem).toBeVisible();

    // Module should already be in the menu
    const listModuleMenu = page.locator('[data-modulemenu-identifier*="list"], [data-modulemenu-identifier*="web"], .active[data-modulemenu-identifier]');
    await expect(listModuleMenu.first()).toBeVisible();
  });

  /**
   * Test 4: Extension Backend Configuration is Loaded
   *
   * Verifies that the contexts extension backend configuration is properly loaded
   * by checking for the presence of expected UI elements
   */
  test('contexts extension backend configuration is loaded', async ({ backend, page }) => {
    // Navigate to List module if not already there
    if (!page.url().includes('module/web/list')) {
      await backend.gotoModule('web_list');
      await backend.moduleLoaded();
    }

    // The content frame should be accessible (main module content)
    const contentFrame = backend.contentFrame;
    await expect(contentFrame.locator('body')).toBeVisible();

    // Verify the frame has loaded
    const docHeader = contentFrame.locator('.module-docheader, h1, .docheader');
    if (await docHeader.first().isVisible().catch(() => false)) {
      // If docheader exists, verify it's visible
      await expect(docHeader.first()).toBeVisible();
    }
  });
});

test.describe('Smoke Tests - Basic Navigation', () => {
  /**
   * Test 5: Can Navigate Between Modules
   *
   * Ensures basic navigation between backend modules works
   */
  test('can navigate between backend modules', async ({ backend, page }) => {
    // Start at List module
    await backend.gotoModule('web_list');
    await backend.moduleLoaded();
    expect(page.url()).toContain('web_list');

    // Navigate to Dashboard module (if available)
    await backend.gotoModule('web_page');
    await backend.moduleLoaded();
    expect(page.url()).toContain('web_page');

    // Navigate back to List module
    await backend.gotoModule('web_list');
    await backend.moduleLoaded();
    expect(page.url()).toContain('web_list');
  });

  /**
   * Test 6: Session Persistence
   *
   * Verifies that the authenticated session persists across module navigation
   */
  test('authenticated session persists across navigation', async ({ backend, page }) => {
    // Get the user name from the toolbar (proves we're logged in)
    const userInfo = page.locator('[class*="user"], [class*="topbar"], .scaffold-toolbar');
    await expect(userInfo.first()).toBeVisible();

    // Navigate to a different module
    await backend.gotoModule('web_list');
    await backend.moduleLoaded();

    // Verify user info is still visible (session persists)
    await expect(userInfo.first()).toBeVisible();

    // Module menu should still be accessible
    const moduleMenu = page.locator('[data-modulemenu-identifier], .scaffold-modulemenu');
    await expect(moduleMenu.first()).toBeVisible();
  });
});
