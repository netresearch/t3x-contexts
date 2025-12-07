/**
 * TYPO3-specific Playwright configuration for contexts extension
 *
 * Environment variables:
 * - PLAYWRIGHT_BASE_URL: Base URL for the TYPO3 backend (default: https://contexts.ddev.site/typo3/)
 * - PLAYWRIGHT_ADMIN_USERNAME: Admin username (default: admin)
 * - PLAYWRIGHT_ADMIN_PASSWORD: Admin password (default: password)
 */
export default {
  // Base URL with trailing slash for relative navigation
  // Example: page.goto('module/web/layout') navigates to {baseUrl}module/web/layout
  // Use v13 by default (v12.contexts.ddev.site also available)
  baseUrl: process.env.PLAYWRIGHT_BASE_URL ?? 'https://v13.contexts.ddev.site/typo3/',

  // Backend admin credentials (default: admin / joh316 from DDEV setup)
  admin: {
    username: process.env.PLAYWRIGHT_ADMIN_USERNAME ?? 'admin',
    password: process.env.PLAYWRIGHT_ADMIN_PASSWORD ?? 'joh316',
  },
};
