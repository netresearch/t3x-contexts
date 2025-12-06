# Playwright E2E Tests for TYPO3 Contexts Extension

End-to-end tests for the TYPO3 contexts extension using Playwright.

## Prerequisites

- Node.js 22.18 or higher (see `.nvmrc`)
- npm 11.5.2 or higher
- Running TYPO3 instance with contexts extension installed

## Installation

```bash
# Install Node.js dependencies
npm install

# Install Playwright browsers
npm run playwright:install
```

## Configuration

The tests can be configured via environment variables:

| Variable | Description | Default |
|----------|-------------|---------|
| `PLAYWRIGHT_BASE_URL` | TYPO3 backend base URL | `https://contexts.ddev.site/typo3/` |
| `PLAYWRIGHT_ADMIN_USERNAME` | Admin username | `admin` |
| `PLAYWRIGHT_ADMIN_PASSWORD` | Admin password | `password` |

### Example: Local DDEV Setup

```bash
export PLAYWRIGHT_BASE_URL="https://contexts.ddev.site/typo3/"
export PLAYWRIGHT_ADMIN_USERNAME="admin"
export PLAYWRIGHT_ADMIN_PASSWORD="your-password"
```

### Example: Docker Setup

```bash
export PLAYWRIGHT_BASE_URL="http://web:80/typo3/"
export PLAYWRIGHT_ADMIN_USERNAME="admin"
export PLAYWRIGHT_ADMIN_PASSWORD="password"
```

## Running Tests

```bash
# Run all tests
npm run playwright:run

# Open test UI (interactive mode)
npm run playwright:open

# View test reports
npm run playwright:report

# Generate test code (record interactions)
npm run playwright:codegen
```

## Test Structure

```
tests/playwright/
├── config.ts                           # Environment configuration
├── helper/
│   └── login.setup.ts                  # Backend authentication
├── fixtures/
│   └── setup-fixtures.ts               # Page Object Models
├── e2e/
│   └── context-management.spec.ts      # Context CRUD tests
└── accessibility/                      # Accessibility tests (future)
```

## Writing Tests

Tests use custom fixtures for TYPO3 backend interaction:

```typescript
import { test, expect } from '../fixtures/setup-fixtures';

test('my test', async ({ backend, page }) => {
  // Navigate to a module
  await backend.gotoModule('contexts');

  // Interact with content frame
  const element = backend.contentFrame.getByText('Context');
  await expect(element).toBeVisible();
});
```

## Test Projects

The test suite is organized into projects:

- **login setup**: Authenticates with TYPO3 backend (runs first)
- **e2e**: End-to-end functional tests
- **accessibility**: Accessibility compliance tests

All test projects (except login setup) reuse the authenticated session state.

## Accessibility Testing

Tests include basic accessibility checks using `@axe-core/playwright`:

```typescript
import { AxeBuilder } from '@axe-core/playwright';

test('accessibility', async ({ page }) => {
  const results = await new AxeBuilder({ page })
    .analyze();

  expect(results.violations).toHaveLength(0);
});
```

## CI/CD Integration

In CI environments:
- Tests run sequentially (1 worker)
- Failed tests retry up to 2 times
- `fullyParallel: false` prevents race conditions

## Debugging

- Screenshots are captured on test failure
- Videos are retained on failure
- Traces are recorded on first retry

Access debug artifacts in:
- Reports: `../../typo3temp/var/tests/playwright-reports/`
- Results: `../../typo3temp/var/tests/playwright-results/`

## Common Issues

### SSL Certificate Errors

The configuration includes `ignoreHTTPSErrors: true` for development environments using self-signed certificates.

### Module Not Found

Ensure the contexts module is registered and accessible in your TYPO3 backend. Check that the module identifier matches the one in your extension configuration.

### Authentication Failures

Verify credentials and TYPO3 backend URL are correct. Check that the backend login form uses the expected labels ("Username", "Password", "Login").
