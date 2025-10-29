# CodeQL Setup Fix

## Issue

The CodeQL workflow is failing with the error:
```
Code Scanning could not process the submitted SARIF file:
CodeQL analyses from advanced configurations cannot be processed when the default setup is enabled
```

## Root Cause

This repository has both:
1. An advanced CodeQL configuration (`.github/workflows/codeql-analysis.yml`)
2. GitHub's default CodeQL setup enabled in repository settings

GitHub doesn't allow both to be active simultaneously.

## Solution

A repository administrator needs to disable the default CodeQL setup:

### Steps to Fix:

1. **Go to Repository Settings**:
   - Navigate to the repository on GitHub
   - Click on the **"Security"** tab
   - Select **"Code scanning"** from the left sidebar

2. **Disable Default Setup**:
   - Find the **"CodeQL"** section
   - Look for a **"Configure"** or **"Disable"** button next to "Default setup"
   - Click **"Disable"** to turn off the default setup

3. **Verify Advanced Setup**:
   - After disabling default setup, the advanced configuration in `.github/workflows/codeql-analysis.yml` will take effect
   - The workflow should run successfully on the next push or pull request

### Alternative Approach

If you prefer to use GitHub's default setup instead of the advanced configuration:

1. **Delete the Advanced Workflow**:
   - Remove `.github/workflows/codeql-analysis.yml`
   - Keep the default setup enabled

2. **Configure Default Setup**:
   - The default setup will automatically scan JavaScript/TypeScript files
   - It requires no additional configuration

**Trade-offs of Default vs Advanced Setup**:
- **Default Setup**: Simpler, zero-configuration, automatic updates
- **Advanced Setup**: Custom scheduling, specific language configurations, custom queries, more control over scan triggers

## Current Workflow Features

The advanced configuration (`.github/workflows/codeql-analysis.yml`) provides:
- JavaScript/TypeScript scanning
- Scheduled weekly scans (Mondays at 6:30 AM)
- Trigger on pushes and pull requests to master branch
- Custom configuration options

## Files Fixed in This PR

- **Renamed**: `Tests/Functional/Fixtures/page.ts` → `Tests/Functional/Fixtures/page.typoscript`
  - This file contained TypoScript configuration, not TypeScript code
  - The incorrect extension was causing parsing errors in CodeQL
  - This change was completed in this pull request

## Next Steps

Once the default setup is disabled by a repository administrator, the CodeQL workflow should run successfully without conflicts.