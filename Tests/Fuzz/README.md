# Fuzz Testing

This directory contains fuzz testing targets for the contexts extension.

## Overview

Fuzz testing automatically generates random/mutated inputs to find crashes, memory exhaustion, or unexpected exceptions in code that parses untrusted input.

## Targets

| Target | Description | Corpus |
|--------|-------------|--------|
| `FlexFormParserTarget.php` | Tests FlexForm XML parsing in AbstractContext | `corpus/flexform/` |
| `CombinationExpressionTarget.php` | Tests logical expression parsing | `corpus/expression/` |
| `IpMatchingTarget.php` | Tests IP address validation and matching | `corpus/ip/` |

## Running Fuzz Tests

### Via runTests.sh (Recommended)

```bash
# Run all fuzz targets (10,000 iterations each)
Build/Scripts/runTests.sh fuzz

# Run specific target
Build/Scripts/runTests.sh fuzz Tests/Fuzz/FlexFormParserTarget.php

# Custom iteration count
Build/Scripts/runTests.sh fuzz Tests/Fuzz/FlexFormParserTarget.php 50000
```

### Via DDEV

```bash
ddev exec Build/Scripts/runTests.sh fuzz
```

### Directly

```bash
vendor/bin/php-fuzzer fuzz Tests/Fuzz/FlexFormParserTarget.php Tests/Fuzz/corpus/flexform --max-runs 10000
```

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

The `corpus/` directory contains seed inputs that the fuzzer uses as starting points. Include variety:

- Valid minimal inputs
- Valid complex inputs
- Edge cases (empty, very long)
- Malformed inputs
- Special characters

## CI Integration

Fuzz testing is typically **not run in CI** due to time requirements. Run:
- Locally before releases
- Weekly on schedule for security-critical code
