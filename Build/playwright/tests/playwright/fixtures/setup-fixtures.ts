/**
 * Playwright Test Fixtures for TYPO3 Backend Testing
 *
 * Provides reusable Page Object Models for testing TYPO3 backend functionality.
 * Designed for TYPO3 v12/v13 with proper selectors and module navigation.
 *
 * Usage:
 *   import { test, expect } from '../fixtures/setup-fixtures';
 *
 *   test('my test', async ({ backend }) => {
 *     await backend.gotoModule('web_list');
 *     await backend.selectPage(0); // Root page
 *   });
 */
import { test as base, type Locator, type Page, expect } from '@playwright/test';
import config from '../config';

/**
 * Backend Page Object Model for TYPO3 v12/v13
 */
export class BackendPage {
  readonly page: Page;
  readonly moduleMenu: Locator;
  readonly contentFrame: ReturnType<Page['frameLocator']>;
  readonly pageTree: Locator;
  readonly docHeader: Locator;
  private initialized = false;

  constructor(page: Page) {
    this.page = page;
    this.moduleMenu = page.locator('.scaffold-modulemenu');
    this.contentFrame = page.frameLocator('#typo3-contentIframe');
    this.pageTree = page.locator('.scaffold-content-navigation-component');
    this.docHeader = this.contentFrame.locator('.module-docheader');
  }

  /**
   * Ensure we're at the TYPO3 backend (navigate if needed)
   */
  async ensureBackendLoaded(): Promise<void> {
    if (this.initialized) return;

    // Navigate to backend URL
    await this.page.goto(config.baseUrl);
    await this.page.waitForLoadState('networkidle');

    // Wait for module menu to be visible (indicates backend is loaded)
    await this.moduleMenu.waitFor({ state: 'visible', timeout: 15000 });
    this.initialized = true;
  }

  /**
   * Navigate to a TYPO3 backend module by identifier
   * Common identifiers: web_list, web_layout, web_info, site_configuration
   *
   * TYPO3 v13 uses Lit web components for the module menu with ARIA roles.
   * We use accessible names instead of data attributes for reliability.
   */
  async gotoModule(identifier: string): Promise<void> {
    // Ensure we're at the backend first
    await this.ensureBackendLoaded();

    // Use data-modulemenu-identifier attribute (confirmed working in TYPO3 v13)
    // The attribute exists on <a role="menuitem" data-modulemenu-identifier="web_list">
    const moduleLink = this.page.locator(`a[data-modulemenu-identifier="${identifier}"]`);
    await moduleLink.click();
    await this.moduleLoaded();
  }

  /**
   * Wait for module content to finish loading
   */
  async moduleLoaded(): Promise<void> {
    await this.page.waitForLoadState('networkidle');
    // Wait for content frame to be interactive
    await this.contentFrame.locator('body').waitFor({ state: 'visible' });
  }

  /**
   * Select a page in the page tree by its ID
   * Use pid=0 for root level (where context records are stored)
   */
  async selectPage(pageId: number): Promise<void> {
    if (pageId === 0) {
      // Click on root node in page tree
      const rootNode = this.page.locator('.scaffold-content-navigation-component [data-tree-node-uid="0"]');
      if (await rootNode.isVisible()) {
        await rootNode.click();
        await this.moduleLoaded();
      }
    } else {
      // Click on specific page node
      const pageNode = this.page.locator(`.scaffold-content-navigation-component [data-tree-node-uid="${pageId}"]`);
      await pageNode.click();
      await this.moduleLoaded();
    }
  }

  /**
   * Click "Create new record" button in List module
   */
  async clickCreateNewRecord(): Promise<void> {
    // TYPO3 v12/v13: Look for the create button in the module content
    const createButton = this.contentFrame.locator('a[title*="Create"], a[title*="create"], button[title*="Create"]').first();
    if (await createButton.isVisible()) {
      await createButton.click();
      await this.moduleLoaded();
    }
  }

  /**
   * Select a table type when creating a new record
   */
  async selectRecordType(table: string): Promise<void> {
    // Look for the table link in the new record wizard
    const tableLink = this.contentFrame.locator(`a[data-table="${table}"], a[href*="table=${table}"]`).first();
    if (await tableLink.isVisible()) {
      await tableLink.click();
      await this.moduleLoaded();
    } else {
      // Fallback: look for text link
      const textLink = this.contentFrame.getByRole('link', { name: /context/i });
      await textLink.click();
      await this.moduleLoaded();
    }
  }

  /**
   * Fill a FormEngine text input field
   */
  async fillFormField(fieldName: string, value: string): Promise<void> {
    // FormEngine uses data-formengine-input-name attribute
    const input = this.contentFrame.locator(
      `input[data-formengine-input-name*="[${fieldName}]"], ` +
      `input[name*="[${fieldName}]"]`
    ).first();
    await input.fill(value);
  }

  /**
   * Select an option in a FormEngine select field
   */
  async selectFormOption(fieldName: string, optionValue: string): Promise<void> {
    const select = this.contentFrame.locator(
      `select[data-formengine-input-name*="[${fieldName}]"], ` +
      `select[name*="[${fieldName}]"]`
    ).first();
    await select.selectOption({ value: optionValue });
  }

  /**
   * Check or uncheck a FormEngine checkbox
   */
  async setFormCheckbox(fieldName: string, checked: boolean): Promise<void> {
    const checkbox = this.contentFrame.locator(
      `input[type="checkbox"][name*="[${fieldName}]"]`
    ).first();
    if (checked) {
      await checkbox.check();
    } else {
      await checkbox.uncheck();
    }
  }

  /**
   * Save the current record via DocHeader button
   */
  async saveRecord(): Promise<void> {
    // Primary save button in DocHeader
    const saveButton = this.contentFrame.locator(
      'button[name="_savedok"], ' +
      'button.t3js-editform-submitButton, ' +
      '.module-docheader button[title*="Save"]'
    ).first();
    await saveButton.click();
    await this.moduleLoaded();
  }

  /**
   * Save and close the current record
   */
  async saveAndCloseRecord(): Promise<void> {
    const saveCloseButton = this.contentFrame.locator(
      'button[name="_saveandclosedok"], ' +
      '.module-docheader button[title*="Save and close"]'
    ).first();
    await saveCloseButton.click();
    await this.moduleLoaded();
  }

  /**
   * Close the current form without saving
   */
  async closeForm(): Promise<void> {
    const closeButton = this.contentFrame.locator(
      '.module-docheader a[title*="Close"], ' +
      '.module-docheader button[title*="Close"]'
    ).first();
    await closeButton.click();
    await this.moduleLoaded();
  }

  /**
   * Wait for a specific backend response
   */
  async waitForModuleResponse(urlPattern: string | RegExp): Promise<void> {
    await this.page.waitForResponse((response) => {
      const url = response.url();
      const matches =
        typeof urlPattern === 'string'
          ? url.includes(urlPattern)
          : urlPattern.test(url);
      return matches && response.status() === 200;
    });
  }

  /**
   * Check if a flash message (success/error) is displayed
   */
  async hasFlashMessage(type: 'success' | 'error' | 'warning' | 'info'): Promise<boolean> {
    const messageClass = `.alert-${type}, .typo3-message-${type}`;
    const message = this.page.locator(messageClass);
    return await message.isVisible();
  }

  /**
   * Wait for success flash message
   */
  async waitForSuccessMessage(): Promise<void> {
    await this.page.locator('.alert-success, .typo3-message-success').waitFor({
      state: 'visible',
      timeout: 10000
    });
  }

  /**
   * Find a record row in the List module by title
   */
  getRecordRowByTitle(title: string): Locator {
    return this.contentFrame.locator(`tr:has-text("${title}")`);
  }

  /**
   * Click edit button for a specific record in the List module
   */
  async editRecord(title: string): Promise<void> {
    const row = this.getRecordRowByTitle(title);
    const editButton = row.locator('a[title*="Edit"], button[title*="Edit"]').first();
    await editButton.click();
    await this.moduleLoaded();
  }

  /**
   * Click delete button for a specific record in the List module
   */
  async deleteRecord(title: string): Promise<void> {
    const row = this.getRecordRowByTitle(title);
    const deleteButton = row.locator('a[title*="Delete"], button[title*="Delete"]').first();
    await deleteButton.click();
  }

  /**
   * Navigate to page editing
   */
  async gotoPageProperties(pageId: number): Promise<void> {
    await this.ensureBackendLoaded();
    await this.gotoModule('web_info');
    await this.selectPage(pageId);
    // Click page properties icon or use context menu
    const pagePropsLink = this.contentFrame.locator('a[title*="Page properties"]').first();
    if (await pagePropsLink.isVisible()) {
      await pagePropsLink.click();
      await this.moduleLoaded();
    }
  }

  /**
   * Navigate to a specific tab in FormEngine
   */
  async gotoFormTab(tabName: string): Promise<void> {
    const tab = this.contentFrame.locator(`.nav-tabs a:has-text("${tabName}")`);
    await tab.click();
  }
}

/**
 * Modal Dialog Page Object Model
 */
export class Modal {
  readonly page: Page;
  readonly container: Locator;
  readonly title: Locator;
  readonly closeButton: Locator;

  constructor(page: Page) {
    this.page = page;
    this.container = page.locator('.modal.show, .modal[aria-modal="true"]');
    this.title = this.container.locator('.modal-title');
    this.closeButton = this.container.locator('[data-bs-dismiss="modal"]');
  }

  /**
   * Check if modal is visible
   */
  async isVisible(): Promise<boolean> {
    return await this.container.isVisible();
  }

  /**
   * Click confirm/OK button in modal
   */
  async confirm(): Promise<void> {
    const confirmButton = this.container.locator(
      'button.btn-danger, ' +
      'button.btn-primary, ' +
      'button[data-method="delete"], ' +
      'button:has-text("OK"), ' +
      'button:has-text("Yes"), ' +
      'button:has-text("Delete")'
    ).first();
    await confirmButton.click();
    // Wait for modal to close
    await this.container.waitFor({ state: 'hidden', timeout: 5000 });
  }

  /**
   * Click cancel button in modal
   */
  async cancel(): Promise<void> {
    await this.closeButton.click();
    await this.container.waitFor({ state: 'hidden' });
  }

  /**
   * Close modal
   */
  async close(): Promise<void> {
    await this.closeButton.click();
    await expect(this.container).not.toBeVisible();
  }
}

/**
 * Fixture type definitions
 */
type BackendFixtures = {
  backend: BackendPage;
  modal: Modal;
};

/**
 * Extended test with TYPO3 backend fixtures
 */
export const test = base.extend<BackendFixtures>({
  backend: async ({ page }, use) => {
    await use(new BackendPage(page));
  },
  modal: async ({ page }, use) => {
    await use(new Modal(page));
  },
});

export { expect, Locator };
