# Playwright E2E Tests for TYPO3 Contexts Extension

End-to-end tests for the TYPO3 contexts extension using Playwright.
Supports TYPO3 v12.4 LTS and v13.x.

## Prerequisites

- Node.js 22.18 or higher (see `.nvmrc`)
- npm 11.5.2 or higher
- Running TYPO3 instance with contexts extension installed (DDEV recommended)
- Admin user credentials for TYPO3 backend

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

### Example: CI/CD Setup

```bash
export PLAYWRIGHT_BASE_URL="http://web:80/typo3/"
export PLAYWRIGHT_ADMIN_USERNAME="admin"
export PLAYWRIGHT_ADMIN_PASSWORD="ci-password"
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
│   └── setup-fixtures.ts               # Page Object Models for TYPO3 v12/v13
├── e2e/
│   ├── context-management.spec.ts      # Context CRUD tests via List module
│   └── context-visibility.spec.ts      # Page/content visibility tests
└── accessibility/                      # Accessibility tests (future)
```

## Test Coverage

### Context Record Management (`context-management.spec.ts`)

Tests for managing context records via the TYPO3 List module:

- Navigate to List module and verify access
- Create IP context with configuration
- Create Domain context
- Edit existing context records
- Delete context records
- Form validation (required fields)
- Context configuration options (inversion, session storage, disabled state)

### Context Visibility (`context-visibility.spec.ts`)

Tests for context-based visibility on pages and content:

- Access Contexts tab in page properties
- Set page visibility based on context
- Configure menu visibility (tx_contexts_nav)
- Access Contexts tab in content elements
- Set content element visibility
- Persistence of context settings
- Context icon overlays in page tree
- Combination context creation

## Writing Tests

Tests use custom Page Object Model fixtures for TYPO3 backend interaction:

```typescript
import { test, expect } from '../fixtures/setup-fixtures';

test('my test', async ({ backend, page }) => {
  // Navigate to List module
  await backend.gotoModule('web_list');

  // Fill a form field
  await backend.fillFormField('title', 'My Context');

  // Save the record
  await backend.saveAndCloseRecord();

  // Verify in content frame
  const element = backend.contentFrame.getByText('My Context');
  await expect(element).toBeVisible();
});
```

### Available Fixtures

**BackendPage (`backend`)**
- `gotoModule(identifier)` - Navigate to backend module
- `moduleLoaded()` - Wait for module to load
- `selectPage(pageId)` - Select page in tree
- `fillFormField(name, value)` - Fill FormEngine input
- `selectFormOption(name, value)` - Select dropdown option
- `setFormCheckbox(name, checked)` - Toggle checkbox
- `saveRecord()` - Save current record
- `saveAndCloseRecord()` - Save and return to list
- `gotoFormTab(tabName)` - Navigate to form tab
- `getRecordRowByTitle(title)` - Find record in list
- `editRecord(title)` - Open record for editing
- `deleteRecord(title)` - Delete a record

**Modal (`modal`)**
- `isVisible()` - Check if modal is shown
- `confirm()` - Click confirm/delete button
- `cancel()` - Click cancel button
- `close()` - Close the modal

## Test Projects

The test suite is organized into projects:

| Project | Description | Dependencies |
|---------|-------------|--------------|
| login setup | Authenticates with TYPO3 backend | None |
| e2e | End-to-end functional tests | login setup |
| accessibility | Accessibility compliance tests | login setup |

All test projects (except login setup) reuse the authenticated session state stored in `.auth/login.json`.

## Accessibility Testing

Tests can include accessibility checks using `@axe-core/playwright`:

```typescript
import { AxeBuilder } from '@axe-core/playwright';

test('accessibility', async ({ page }) => {
  const results = await new AxeBuilder({ page })
    .include('#typo3-contentIframe')
    .analyze();

  const critical = results.violations.filter(
    v => v.impact === 'critical' || v.impact === 'serious'
  );
  expect(critical).toHaveLength(0);
});
```

## DDEV Integration

The tests are designed to work with the project's DDEV setup:

```bash
# Start DDEV
ddev start

# Run tests from Build/playwright directory
cd Build/playwright
npm run playwright:run

# Or run from project root with full path
npx --prefix Build/playwright playwright test
```

## CI/CD Integration

In CI environments:
- Tests run sequentially (1 worker)
- Failed tests retry up to 2 times
- JUnit report generated for CI integration
- Screenshots and videos retained on failure

### GitHub Actions Example

```yaml
- name: Run E2E tests
  run: |
    cd Build/playwright
    npm ci
    npx playwright install --with-deps
    npm run playwright:run
  env:
    PLAYWRIGHT_BASE_URL: https://contexts.ddev.site/typo3/
    PLAYWRIGHT_ADMIN_USERNAME: admin
    PLAYWRIGHT_ADMIN_PASSWORD: ${{ secrets.TYPO3_ADMIN_PASSWORD }}
```

## Debugging

### Artifacts
- Screenshots: `../../typo3temp/var/tests/playwright-results/`
- Videos: `../../typo3temp/var/tests/playwright-results/`
- Traces: Available on first retry
- Reports: `../../typo3temp/var/tests/playwright-reports/`

### Interactive Mode
```bash
# Open Playwright UI for debugging
npm run playwright:open

# Run specific test file
npx playwright test context-management.spec.ts

# Run with debug mode
PWDEBUG=1 npx playwright test
```

### Codegen (Record Tests)
```bash
# Open browser and record interactions
npm run playwright:codegen https://contexts.ddev.site/typo3/
```

## Common Issues

### SSL Certificate Errors

The configuration includes `ignoreHTTPSErrors: true` for DDEV's self-signed certificates.

### Module Not Found

The contexts extension doesn't have a dedicated backend module. Context records are managed via the **List module** (`web_list`). Ensure you're selecting the root page (pid=0) to see context records.

### Authentication Failures

1. Verify TYPO3 backend URL is correct
2. Check admin credentials
3. Ensure DDEV is running: `ddev status`
4. Try clearing authentication state: `rm -rf .auth/`

### Slow Tests

Increase timeouts in `playwright.config.ts` if tests are timing out:
- `timeout`: Global test timeout
- `expect.timeout`: Assertion timeout
- `use.actionTimeout`: Individual action timeout

### Frame Access Issues

TYPO3 backend uses an iframe (`#typo3-contentIframe`) for module content. Always use `backend.contentFrame` to access elements within the module:

```typescript
// Correct: Access via content frame
const element = backend.contentFrame.locator('input[name*="title"]');

// Wrong: Direct page access won't find iframe content
const element = page.locator('input[name*="title"]');
```

## TYPO3 Backend Architecture

Understanding TYPO3's backend structure helps write better tests:

- **Module Menu**: Left sidebar with `[data-modulemenu-identifier="..."]` attributes
- **Content Frame**: `#typo3-contentIframe` contains module content
- **Page Tree**: `.scaffold-content-navigation-component` for page navigation
- **DocHeader**: `.module-docheader` contains save/close buttons
- **FormEngine**: Record editing with `data[table][uid][field]` naming
- **Flash Messages**: `.alert-success`, `.alert-danger` for notifications

## Extension-Specific Notes

### Context Records
- Stored in `tx_contexts_contexts` table
- Admin-only access (`adminOnly' => true`)
- Root level storage (`rootLevel' => -1`)
- Managed via List module, not a dedicated module

### Context Types
- IP: Match by IP address/range
- Domain: Match by domain name
- HttpHeader: Match HTTP request headers
- QueryParameter: Match URL query parameters
- Session: Match session variables
- Combination: Logical combinations of other contexts

### Visibility Fields
- `tx_contexts_visibility`: General visibility control (pages, tt_content)
- `tx_contexts_nav`: Menu visibility (pages only)
