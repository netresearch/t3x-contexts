<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Security Policy

## Supported Versions

| Version | TYPO3          | Supported                                          |
|---------|----------------|----------------------------------------------------|
| 5.x     | 13.4, 14.3     | :white_check_mark:                                 |
| 4.x     | 12.4, 13.4     | :white_check_mark: security fixes until 2027-01-30 |
| < 4.0   | 11.5 and older | :x:                                                |

Following the [organisation policy](https://github.com/netresearch/.github/blob/main/SECURITY.md#supported-versions), the previous major version receives security fixes for six months after the next major release. 5.0.1, the first release with the 5.x code, was published on 2026-07-30; the `v5.0.0` tag carries 4.x code (see [CHANGELOG.md](CHANGELOG.md)).

## Reporting a Vulnerability

If you discover a security vulnerability within this extension, please report via [GitHub Security Advisories](https://github.com/netresearch/t3x-contexts/security/advisories/new).

**Please do not report security vulnerabilities through public GitHub issues.**

We will acknowledge your report within 48 hours and provide a more detailed response within 7 days indicating the next steps in handling your report.

After the initial reply to your report, we will endeavor to keep you informed of the progress towards a fix and full announcement, and may ask for additional information or guidance.

## Security Guarantees and Limitations

What the extension does and does not protect, its threat model and the countermeasures in the code are described in [docs/SECURITY-ASSURANCE.md](docs/SECURITY-ASSURANCE.md). In short: contexts select content for personalisation; they are not an access control, because visitors can set the headers and parameters that contexts match on.

## Security Update Process

1. Security issues are handled with high priority
2. A fix will be developed and tested
3. A new release will be published
4. The vulnerability will be disclosed after users have had reasonable time to update
