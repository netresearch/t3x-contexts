<!-- SPDX-License-Identifier: AGPL-3.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->
# Fuzz Testing

This directory contains fuzz testing targets for the contexts extension.

## Overview

Fuzz testing generates random or mutated inputs to find crashes, memory exhaustion, or unexpected exceptions in code that parses configuration and request data.

There are two kinds of fuzz tests here:

- `ContextInputFuzzTest.php` is a PHPUnit test. It feeds the seed corpus and inputs generated from a fixed seed into the combination expression evaluator, the domain matching and the IP range comparison, and it fails on any exception, warning or notice that the code does not document. The `fuzz` job of `.github/workflows/checks.yml` runs it on every pull request (the shared `fuzz.yml` workflow runs the `Fuzz` testsuite of `Build/phpunit.xml`).
- The `*Target.php` files are targets for [nikic/php-fuzzer](https://github.com/nikic/PHP-Fuzzer), a coverage-guided fuzzer for longer local runs. They are not run in CI.

## PHPUnit fuzz suite

```bash
vendor/bin/phpunit -c Build/phpunit.xml --testsuite Fuzz --no-coverage
```

A failure message names the input that failed. Inputs come from a fixed seed, so the same input fails on every run.

## php-fuzzer targets

| Target | Description | Corpus |
|--------|-------------|--------|
| `FlexFormParserTarget.php` | FlexForm XML parsing in AbstractContext | `corpus/flexform/` |
| `CombinationExpressionTarget.php` | Logical expression parsing and evaluation | `corpus/expression/` |
| `IpMatchingTarget.php` | IP address validation and matching | `corpus/ip/` |

php-fuzzer adds the inputs it finds to the corpus directory it is given and removes inputs it has reduced, so pass a copy of the corpus:

```bash
cp -r Tests/Fuzz/corpus/flexform /tmp/flexform-corpus
vendor/bin/php-fuzzer fuzz Tests/Fuzz/FlexFormParserTarget.php /tmp/flexform-corpus --max-runs 10000
```

php-fuzzer prints a line only when an input reaches new code, so the last line can show a lower run count than `--max-runs`.

## Interpreting Results

| Result | Meaning | Action |
|--------|---------|--------|
| NEW | Found input triggering new code path | Good - corpus expanding |
| REDUCE | Simplified input while keeping coverage | Good - efficient corpus |
| CRASH | Input caused exception/error | **Fix the bug** |
| TIMEOUT | Input caused infinite loop | **Fix performance issue** |
| OOM | Input caused memory exhaustion | **Fix memory handling** |

## Adding New Targets

1. Create `<Name>Target.php` in this directory
2. Create corpus directory at `corpus/<name>/`
3. Add seed inputs to corpus directory
4. Run the fuzzer to expand corpus

## Seed Corpus

The `corpus/` directory contains seed inputs that the fuzzers use as starting points. Include variety:

- Valid minimal inputs
- Valid complex inputs
- Edge cases (empty, very long)
- Malformed inputs
- Special characters
