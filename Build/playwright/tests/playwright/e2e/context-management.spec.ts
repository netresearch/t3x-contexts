/**
 * E2E Tests for Context Management in TYPO3 Backend
 *
 * Tests the complete workflow of creating, editing, and deleting contexts
 * through the TYPO3 backend module.
 */
import { test, expect } from '../fixtures/setup-fixtures';

test.describe('Context Management', () => {
  test.beforeEach(async ({ backend, page }) => {
    // Navigate to contexts module
    await page.goto('/');
    await backend.gotoModule('contexts');
    await backend.moduleLoaded();
  });

  test('navigate to contexts module', async ({ page, backend }) => {
    // Verify we're in the contexts module
    await expect(page).toHaveURL(/.*contexts.*/);

    // Verify module content is visible
    await expect(backend.contentFrame.locator('body')).toBeVisible();
  });

  test('create a new context', async ({ page, backend }) => {
    // Click the "Create new context" button
    const createButton = backend.contentFrame.getByRole('button', {
      name: /create.*context/i
    });
    await createButton.click();

    // Fill in the context form
    const titleField = backend.contentFrame.getByLabel(/title|name/i);
    await titleField.fill('Test Context');

    // Fill in the alias/identifier field
    const aliasField = backend.contentFrame.getByLabel(/alias|identifier/i);
    await aliasField.fill('test_context');

    // Set context type if available
    const typeSelect = backend.contentFrame.locator('select[name*="type"]');
    if (await typeSelect.isVisible()) {
      await typeSelect.selectOption({ index: 1 });
    }

    // Save the context
    const saveButton = backend.contentFrame.getByRole('button', {
      name: /save|create/i
    });
    await saveButton.click();

    // Wait for save response
    await backend.waitForModuleResponse(/contexts.*save|update/);

    // Verify success message
    const successMessage = page.locator('.alert-success, .message-success');
    await expect(successMessage).toBeVisible({ timeout: 5000 });

    // Verify context appears in the list
    const contextRow = backend.contentFrame.getByText('Test Context');
    await expect(contextRow).toBeVisible();
  });

  test('edit context configuration', async ({ page, backend }) => {
    // First create a context to edit
    const createButton = backend.contentFrame.getByRole('button', {
      name: /create.*context/i
    });

    if (await createButton.isVisible()) {
      await createButton.click();

      const titleField = backend.contentFrame.getByLabel(/title|name/i);
      await titleField.fill('Context to Edit');

      const aliasField = backend.contentFrame.getByLabel(/alias|identifier/i);
      await aliasField.fill('context_to_edit');

      const saveButton = backend.contentFrame.getByRole('button', {
        name: /save|create/i
      });
      await saveButton.click();

      await backend.moduleLoaded();
    }

    // Find and click edit button for the context
    const editButton = backend.contentFrame.locator(
      'a[title*="Edit"], button[title*="Edit"]'
    ).first();
    await editButton.click();
    await backend.moduleLoaded();

    // Modify the context title
    const titleField = backend.contentFrame.getByLabel(/title|name/i);
    await titleField.clear();
    await titleField.fill('Edited Context Title');

    // Save changes
    const saveButton = backend.contentFrame.getByRole('button', {
      name: /save|update/i
    });
    await saveButton.click();

    // Wait for save response
    await backend.waitForModuleResponse(/contexts.*save|update/);

    // Verify success message
    const successMessage = page.locator('.alert-success, .message-success');
    await expect(successMessage).toBeVisible({ timeout: 5000 });

    // Verify updated context appears in the list
    const updatedContext = backend.contentFrame.getByText('Edited Context Title');
    await expect(updatedContext).toBeVisible();
  });

  test('delete context', async ({ page, backend, modal }) => {
    // First create a context to delete
    const createButton = backend.contentFrame.getByRole('button', {
      name: /create.*context/i
    });

    if (await createButton.isVisible()) {
      await createButton.click();

      const titleField = backend.contentFrame.getByLabel(/title|name/i);
      await titleField.fill('Context to Delete');

      const aliasField = backend.contentFrame.getByLabel(/alias|identifier/i);
      await aliasField.fill('context_to_delete');

      const saveButton = backend.contentFrame.getByRole('button', {
        name: /save|create/i
      });
      await saveButton.click();

      await backend.moduleLoaded();
    }

    // Find and click delete button for the context
    const deleteButton = backend.contentFrame.locator(
      'a[title*="Delete"], button[title*="Delete"]'
    ).first();
    await deleteButton.click();

    // Confirm deletion in modal dialog
    const confirmButton = page.getByRole('button', {
      name: /delete|confirm|yes/i
    });
    await expect(confirmButton).toBeVisible();
    await confirmButton.click();

    // Wait for delete response
    await backend.waitForModuleResponse(/contexts.*delete|remove/);

    // Verify success message
    const successMessage = page.locator('.alert-success, .message-success');
    await expect(successMessage).toBeVisible({ timeout: 5000 });

    // Verify context is removed from the list
    const deletedContext = backend.contentFrame.getByText('Context to Delete');
    await expect(deletedContext).not.toBeVisible();
  });

  test('context list accessibility', async ({ page, backend }) => {
    const { default: AxeBuilder } = await import('@axe-core/playwright');

    // Run accessibility scan on the contexts module
    const accessibilityScanResults = await new AxeBuilder({ page })
      .include('iframe#typo3-contentIframe')
      .analyze();

    // Verify no critical accessibility violations
    const criticalViolations = accessibilityScanResults.violations.filter(
      (violation) => violation.impact === 'critical' || violation.impact === 'serious'
    );

    expect(criticalViolations).toHaveLength(0);
  });
});
