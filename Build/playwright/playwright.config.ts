/**
 * Playwright E2E Test Configuration for TYPO3 Extension: contexts
 *
 * Supports TYPO3 v12.4 LTS and v13.x with proper backend testing patterns.
 *
 * Based on TYPO3 Core configuration:
 * @see https://github.com/TYPO3/typo3/blob/main/Build/playwright.config.ts
 */
import { defineConfig } from '@playwright/test';
import config from './tests/playwright/config';

export default defineConfig({
  testDir: './tests/playwright',
  timeout: 60000, // Increased for slower DDEV environments
  expect: {
    timeout: 15000, // Increased for iframe content loading
  },
  fullyParallel: false, // TYPO3 backend tests should run sequentially
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 1, // Retry once locally for flaky selectors
  workers: 1, // Single worker for backend tests to avoid session conflicts
  reporter: [
    ['list'],
    ['html', { outputFolder: '../../typo3temp/var/tests/playwright-reports' }],
    ['junit', { outputFile: '../../typo3temp/var/tests/playwright-results/results.xml' }],
  ],
  outputDir: '../../typo3temp/var/tests/playwright-results',

  use: {
    baseURL: config.baseUrl,
    ignoreHTTPSErrors: true, // Required for DDEV self-signed certificates
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    // Viewport suitable for TYPO3 backend
    viewport: { width: 1920, height: 1080 },
    // Slow down actions for debugging
    actionTimeout: 10000,
    navigationTimeout: 30000,
  },

  projects: [
    // Authentication setup - runs first
    {
      name: 'login setup',
      testMatch: /helper\/login\.setup\.ts/,
    },
    // E2E functional tests
    {
      name: 'e2e',
      testMatch: /e2e\/.*\.spec\.ts/,
      dependencies: ['login setup'],
      use: {
        storageState: './.auth/login.json',
      },
    },
    // Accessibility tests (separate for focused testing)
    {
      name: 'accessibility',
      testMatch: /accessibility\/.*\.spec\.ts/,
      dependencies: ['login setup'],
      use: {
        storageState: './.auth/login.json',
      },
    },
  ],

  // Global setup for creating auth directory
  globalSetup: undefined,
  globalTeardown: undefined,
});
