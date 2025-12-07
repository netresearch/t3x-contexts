/**
 * TYPO3 Backend Login Setup for TYPO3 v12/v13
 *
 * This setup file authenticates with the TYPO3 backend and stores
 * the session state for reuse across all tests.
 *
 * Handles:
 * - DDEV landing page redirect to actual backend
 * - TYPO3 v12 classic login form
 * - TYPO3 v13 Lit/Web Components login form
 *
 * @see https://playwright.dev/docs/auth
 */
import { test as setup, expect } from '@playwright/test';
import * as fs from 'fs';
import * as path from 'path';
import config from '../config';

// Ensure auth directory exists
const authDir = path.join(__dirname, '../../.auth');
if (!fs.existsSync(authDir)) {
  fs.mkdirSync(authDir, { recursive: true });
}

setup('authenticate with TYPO3 backend', async ({ page }) => {
  // Navigate to TYPO3 backend (baseUrl already includes /typo3/)
  console.log(`Navigating to: ${config.baseUrl}`);
  await page.goto(config.baseUrl);
  await page.waitForLoadState('domcontentloaded');

  // Take a screenshot for debugging
  const resultsDir = path.join(__dirname, '../../../../typo3temp/var/tests/playwright-results');
  if (!fs.existsSync(resultsDir)) {
    fs.mkdirSync(resultsDir, { recursive: true });
  }

  // Check if we landed on a landing page (DDEV demo page)
  const landingPageIndicator = page.locator('text=Contexts Extension, text=Context-dependent content visibility').first();
  const backendLink = page.locator('a:has-text("TYPO3 Backend"), a[href="/typo3"]').first();

  if (await landingPageIndicator.isVisible({ timeout: 2000 }).catch(() => false)) {
    console.log('Detected landing page, clicking TYPO3 Backend link...');
    await backendLink.click();
    await page.waitForLoadState('networkidle');
  }

  // Wait for page to fully load (TYPO3 v13 uses Lit components)
  await page.waitForLoadState('networkidle');
  await page.waitForTimeout(1000); // Allow Lit components to initialize

  // Check if we're already logged in (session still valid)
  const moduleMenu = page.locator('.scaffold-modulemenu, .t3js-topbar-button-modulemenu, [data-modulemenu-identifier]');
  if (await moduleMenu.isVisible({ timeout: 2000 }).catch(() => false)) {
    console.log('Already logged in, saving state...');
    await page.context().storageState({ path: './.auth/login.json' });
    return;
  }

  // Screenshot before login attempt
  await page.screenshot({ path: path.join(resultsDir, 'login-page.png') });

  // TYPO3 v12/v13 login form selectors
  // v13 uses Lit-based form with specific structure
  const usernameSelectors = [
    'input[name="username"]',
    'input#t3-username',
    '#username',
    'typo3-backend-login input[name="username"]',
    'form input[placeholder*="user" i]',
    'form input[autocomplete="username"]',
  ];

  const passwordSelectors = [
    'input[name="userident"]',
    'input[name="p_field"]',
    'input[type="password"]',
    'input#t3-password',
    '#password',
    'typo3-backend-login input[type="password"]',
  ];

  // Find username field using multiple strategies
  let usernameField = null;
  for (const selector of usernameSelectors) {
    const field = page.locator(selector).first();
    if (await field.isVisible({ timeout: 1000 }).catch(() => false)) {
      usernameField = field;
      console.log(`Found username field with selector: ${selector}`);
      break;
    }
  }

  if (!usernameField) {
    // Log page content for debugging
    const content = await page.content();
    console.log('Page URL:', page.url());
    console.log('Page title:', await page.title());
    await page.screenshot({ path: path.join(resultsDir, 'login-error.png') });

    // Save error context for debugging
    const snapshot = await page.accessibility.snapshot();
    fs.writeFileSync(
      path.join(resultsDir, 'error-context.md'),
      `# Page snapshot\n\n\`\`\`yaml\n${JSON.stringify(snapshot, null, 2)}\n\`\`\``
    );

    throw new Error('Could not find username field. Check login-error.png for details.');
  }

  // Find password field
  let passwordField = null;
  for (const selector of passwordSelectors) {
    const field = page.locator(selector).first();
    if (await field.isVisible({ timeout: 1000 }).catch(() => false)) {
      passwordField = field;
      console.log(`Found password field with selector: ${selector}`);
      break;
    }
  }

  if (!passwordField) {
    throw new Error('Could not find password field');
  }

  // Fill login credentials
  console.log(`Logging in as: ${config.admin.username}`);
  await usernameField.fill(config.admin.username);
  await passwordField.fill(config.admin.password);

  // Find and click login button
  const loginButtonSelectors = [
    'button[type="submit"]',
    'button[name="commandLI"]',
    'button:has-text("Login")',
    'button:has-text("Sign in")',
    'input[type="submit"]',
    'typo3-backend-login button[type="submit"]',
  ];

  let loginButton = null;
  for (const selector of loginButtonSelectors) {
    const btn = page.locator(selector).first();
    if (await btn.isVisible({ timeout: 1000 }).catch(() => false)) {
      loginButton = btn;
      console.log(`Found login button with selector: ${selector}`);
      break;
    }
  }

  if (!loginButton) {
    // Try pressing Enter as fallback
    console.log('No login button found, pressing Enter...');
    await passwordField.press('Enter');
  } else {
    await loginButton.click();
  }

  // Wait for login to complete and backend to load
  await page.waitForLoadState('networkidle');

  // Verify login succeeded by checking for module menu or toolbar
  const successIndicators = [
    '.scaffold-modulemenu',
    '.t3js-topbar-button-modulemenu',
    '#typo3-modulemenu',
    '[data-modulemenu-identifier]',
    '.scaffold-toolbar',
    '.topbar',
  ];

  let loginSucceeded = false;
  for (const selector of successIndicators) {
    const indicator = page.locator(selector).first();
    if (await indicator.isVisible({ timeout: 5000 }).catch(() => false)) {
      loginSucceeded = true;
      console.log(`Login succeeded, found: ${selector}`);
      break;
    }
  }

  if (!loginSucceeded) {
    // Check for error message
    const errorMessage = page.locator('.alert-danger, .typo3-message-error, .callout-danger');
    if (await errorMessage.isVisible({ timeout: 2000 }).catch(() => false)) {
      const errorText = await errorMessage.textContent();
      throw new Error(`Login failed with error: ${errorText}`);
    }

    await page.screenshot({ path: path.join(resultsDir, 'login-failed.png') });
    throw new Error('Login verification failed. Check login-failed.png for details.');
  }

  // Take success screenshot
  await page.screenshot({ path: path.join(resultsDir, 'login-success.png') });

  // Save authentication state for reuse across tests
  await page.context().storageState({ path: './.auth/login.json' });
  console.log('Authentication state saved to .auth/login.json');
});

// Note: Backend accessibility is verified by e2e tests which depend on this setup
// The storage state is saved after successful login and reused by dependent projects
