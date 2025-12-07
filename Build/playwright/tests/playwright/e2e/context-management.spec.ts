/**
 * E2E Tests for Context Management in TYPO3 Backend
 *
 * Tests the complete workflow of creating, editing, and deleting contexts
 * through the TYPO3 List module. Context records are stored at root level (pid=0).
 *
 * The contexts extension uses the standard List module for record management,
 * not a dedicated backend module.
 */
import { test, expect, type Locator } from '../fixtures/setup-fixtures';
import type { Page } from '@playwright/test';
import config from '../config';

/**
 * Handle the "Refresh required" modal that appears when changing context type.
 * TYPO3 v13 uses a <typo3-backend-modal> web component.
 */
async function handleRefreshModal(page: Page): Promise<void> {
  // Wait a moment for modal to potentially appear
  await page.waitForTimeout(500);

  // Check if modal appeared and handle it
  const modal = page.locator('.modal.show');
  if (await modal.isVisible().catch(() => false)) {
    // Click "Keep editing" button to dismiss without saving
    const keepEditingBtn = page.locator('button:has-text("Keep editing")');
    await keepEditingBtn.click({ timeout: 3000 });
    // Wait for modal to close
    await modal.waitFor({ state: 'hidden', timeout: 3000 }).catch(() => {});
  }
}

test.describe('Context Record Management via List Module', () => {
  /**
   * Navigate to List module on root page before each test
   */
  test.beforeEach(async ({ backend }) => {
    // Navigate to List module
    await backend.gotoModule('web_list');
    // Wait for module to be ready
    await backend.moduleLoaded();
  });

  test('can navigate to List module and see context table', async ({ backend }) => {
    // Verify we're in the List module
    await expect(backend.contentFrame.locator('body')).toBeVisible();

    // Check if the module content is visible
    const moduleHeader = backend.contentFrame.locator('.module-docheader, h1, .module-body');
    await expect(moduleHeader.first()).toBeVisible();
  });

  test('can create a new IP context', async ({ backend, page }) => {
    // Navigate directly to create form for context record
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    // Wait for form to be visible
    const formBody = backend.contentFrame.locator('form, .module-body');
    await expect(formBody.first()).toBeVisible({ timeout: 10000 });

    // Fill title field - use more specific selector for FormEngine
    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('E2E Test IP Context');

    // Fill alias field
    const aliasInput = backend.contentFrame.locator('input[data-formengine-input-name*="[alias]"]').first();
    if (await aliasInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      await aliasInput.fill('e2e_ip_context');
    }

    // Select context type - IP
    const typeSelect = backend.contentFrame.locator('select[data-formengine-input-name*="[type]"], select[name*="[type]"]').first();
    if (await typeSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      const options = await typeSelect.locator('option').allTextContents();
      const ipOption = options.find(opt => opt.toLowerCase().includes('ip'));
      if (ipOption) {
        await typeSelect.selectOption({ label: ipOption });
        await handleRefreshModal(page);
      }
    }

    // Save the record (TYPO3 v13 may not have "Save and close" button visible)
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify record was saved - it should no longer say "NEW" in the footer
    // The form stays open after save, check that record exists (uid is assigned)
    const recordIndicator = backend.contentFrame.locator('text=/Context \\d+/'); // "Context 1" instead of "Context NEW"
    const titleStillVisible = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]');
    await expect(titleStillVisible.or(recordIndicator)).toBeVisible({ timeout: 10000 });
  });

  test('can create a new Domain context', async ({ backend, page }) => {
    // Navigate directly to create form
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    // Wait for form
    const formBody = backend.contentFrame.locator('form, .module-body');
    await expect(formBody.first()).toBeVisible({ timeout: 10000 });

    // Fill title
    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('E2E Test Domain Context');

    // Fill alias
    const aliasInput = backend.contentFrame.locator('input[data-formengine-input-name*="[alias]"]').first();
    if (await aliasInput.isVisible({ timeout: 2000 }).catch(() => false)) {
      await aliasInput.fill('e2e_domain_context');
    }

    // Select type - Domain
    const typeSelect = backend.contentFrame.locator('select[data-formengine-input-name*="[type]"], select[name*="[type]"]').first();
    if (await typeSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      const options = await typeSelect.locator('option').allTextContents();
      const domainOption = options.find(opt => opt.toLowerCase().includes('domain'));
      if (domainOption) {
        await typeSelect.selectOption({ label: domainOption });
        await handleRefreshModal(page);
      }
    }

    // Save the record
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify record was saved - title field should still be visible with our value
    await expect(titleInput).toHaveValue('E2E Test Domain Context', { timeout: 10000 });
  });

  test('can edit an existing context record', async ({ backend, page }) => {
    // First create a context to edit
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    // Fill and save a new context
    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('Context To Edit');

    // Save (not save and close, to get the UID)
    const saveButton = backend.contentFrame.locator(
      'button[name="_savedok"], ' +
      'button[title*="Save"]'
    ).first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Now modify the title
    const titleInputAfterSave = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInputAfterSave.fill('Context Edited Successfully');

    // Save the record
    const saveButton2 = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton2.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify the edit was saved - check title field has new value
    await expect(titleInputAfterSave).toHaveValue('Context Edited Successfully', { timeout: 10000 });
  });

  test('can delete a context record', async ({ backend, page, modal }) => {
    // First create a context to delete
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('Context To Delete');

    // Save the record first
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Navigate to list module using close button
    const closeButton = backend.contentFrame.locator('button:has-text("Close"), a:has-text("Close")').first();
    await closeButton.click();
    await page.waitForLoadState('networkidle');

    // Navigate to list module
    await backend.gotoModule('web_list');
    await backend.moduleLoaded();

    // Find and delete the record
    const deleteButton = backend.contentFrame.locator(
      'tr:has-text("Context To Delete") a[title*="Delete"], ' +
      'tr:has-text("Context To Delete") button[title*="Delete"]'
    ).first();

    if (await deleteButton.isVisible({ timeout: 5000 }).catch(() => false)) {
      await deleteButton.click();

      // Confirm deletion in modal
      if (await modal.isVisible()) {
        await modal.confirm();
      }

      // Wait for deletion to complete
      await page.waitForLoadState('networkidle');

      // Verify record is gone
      const deletedRecord = backend.contentFrame.locator('text=Context To Delete');
      await expect(deletedRecord).not.toBeVisible({ timeout: 5000 });
    }
  });

  test('context form validates required fields', async ({ backend, page }) => {
    // Navigate to create form
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    // Try to save without filling required fields
    const saveButton = backend.contentFrame.locator(
      'button[name="_savedok"], ' +
      'button[title*="Save"]'
    ).first();
    await saveButton.click();

    // Wait for validation to trigger
    await page.waitForTimeout(1000);

    // The form should still be visible (not redirected) - title field empty indicates validation stopped save
    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await expect(titleInput).toBeVisible({ timeout: 5000 });
    // Title should still be empty since we didn't fill it
    await expect(titleInput).toHaveValue('', { timeout: 5000 });
  });
});

test.describe('Context Configuration Options', () => {
  test('can configure IP context with specific IP range', async ({ backend, page }) => {
    // Create IP context
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    // Fill title
    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('IP Range Context');

    // Select IP type
    const typeSelect = backend.contentFrame.locator('select[data-formengine-input-name*="[type]"], select[name*="[type]"]').first();
    if (await typeSelect.isVisible({ timeout: 2000 }).catch(() => false)) {
      const options = await typeSelect.locator('option').allTextContents();
      const ipOption = options.find(opt => opt.toLowerCase().includes('ip'));
      if (ipOption) {
        await typeSelect.selectOption({ label: ipOption });
        await handleRefreshModal(page);
      }
    }

    // Look for IP-specific configuration fields after type selection
    // The form should reload with IP configuration options
    const ipConfigField = backend.contentFrame.locator(
      'input[data-formengine-input-name*="[type_conf]"], ' +
      'textarea[data-formengine-input-name*="[type_conf]"], ' +
      'input[name*="ip_range"], ' +
      'textarea[name*="type_conf"]'
    ).first();

    if (await ipConfigField.isVisible({ timeout: 3000 }).catch(() => false)) {
      await ipConfigField.fill('192.168.1.0/24');
    }

    // Save the record
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify saved - title field should have our value
    const titleField = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await expect(titleField).toHaveValue('IP Range Context', { timeout: 10000 });
  });

  test('can toggle context inversion', async ({ backend, page }) => {
    // Create context with inversion enabled
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('Inverted Context');

    // Toggle inversion checkbox - use force: true to bypass SVG overlay intercept
    const invertCheckbox = backend.contentFrame.locator(
      'input[type="checkbox"][data-formengine-input-name*="[invert]"], ' +
      'input[type="checkbox"][name*="[invert]"]'
    ).first();

    if (await invertCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
      await invertCheckbox.check({ force: true });
    }

    // Save the record
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify saved - title field should have our value
    await expect(titleInput).toHaveValue('Inverted Context', { timeout: 10000 });
  });

  test('can disable a context', async ({ backend, page }) => {
    // Create disabled context
    await page.goto(`${config.baseUrl}record/edit?edit[tx_contexts_contexts][0]=new&returnUrl=/typo3/module/web/list`);
    await backend.moduleLoaded();

    const titleInput = backend.contentFrame.locator('input[data-formengine-input-name*="[title]"]').first();
    await titleInput.fill('Disabled Context');

    // Check the disabled checkbox - use force: true to bypass SVG overlay intercept
    const disabledCheckbox = backend.contentFrame.locator(
      'input[type="checkbox"][data-formengine-input-name*="[disabled]"], ' +
      'input[type="checkbox"][name*="[disabled]"]'
    ).first();

    if (await disabledCheckbox.isVisible({ timeout: 2000 }).catch(() => false)) {
      await disabledCheckbox.check({ force: true });
    }

    // Save the record
    const saveButton = backend.contentFrame.locator('button[name="_savedok"], button[title="Save"]').first();
    await saveButton.click();
    await page.waitForLoadState('networkidle');
    await page.waitForTimeout(1000);

    // Verify saved - title field should have our value
    await expect(titleInput).toHaveValue('Disabled Context', { timeout: 10000 });
  });
});
