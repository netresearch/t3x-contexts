<?php

/*
 * Copyright (c) 2026 Netresearch DTT GmbH
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/**
 * This file is part of the package netresearch/contexts.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Netresearch\Contexts\Tests\Fuzz;

use ErrorException;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluator;
use Netresearch\Contexts\Context\Type\Combination\LogicalExpressionEvaluatorException;
use Netresearch\Contexts\Context\Type\DomainContext;
use Netresearch\Contexts\Context\Type\IpContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use ReflectionMethod;
use Throwable;

/**
 * Randomised input for the parts of the extension that read configuration
 * and request data: combination expressions, domain patterns and IP ranges.
 *
 * Inputs are generated from a fixed seed, so a failure reproduces on every
 * run; the message names the input that failed. The seed corpus under
 * corpus/ is fed in first.
 */
#[CoversClass(LogicalExpressionEvaluator::class)]
#[CoversClass(DomainContext::class)]
#[CoversClass(IpContext::class)]
final class ContextInputFuzzTest extends TestCase
{
    private const SEED = 20261007;

    private const ITERATIONS = 2000;

    private Randomizer $random;

    protected function setUp(): void
    {
        parent::setUp();

        $this->random = new Randomizer(new Mt19937(self::SEED));

        // A warning or notice is a finding as well, not only an exception.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    protected function tearDown(): void
    {
        restore_error_handler();

        parent::tearDown();
    }

    #[Test]
    public function combinationExpressionsEvaluateOrAreRejected(): void
    {
        $parts = ['ctx1', 'ctx2', 'ctx3', 'Ctx1', 'a-b', 'a_b', '7', ' ', '  ', '&&', '||', '><', '&', '|', '>', '<',
            '!', '(', ')', 'and', 'or', 'xor', 'AND', "\n", "\t", 'ä', '"', '\\', ''];
        $values = ['ctx1' => true, 'ctx2' => false, 'ctx3' => 'disabled'];

        foreach ($this->inputs('expression', $parts, 12) as $expression) {
            try {
                $result = LogicalExpressionEvaluator::run($expression, $values);
            } catch (LogicalExpressionEvaluatorException) {
                // A rejected expression is a valid outcome.
                continue;
            } catch (Throwable $e) {
                self::fail(\sprintf('Expression %s: %s: %s', json_encode($expression), $e::class, $e->getMessage()));
            }

            self::assertIsBool($result, 'Expression ' . json_encode($expression));
        }
    }

    #[Test]
    public function leadingDotDomainMatchesTheDomainAndItsSubdomainsOnly(): void
    {
        $labels = ['a', 'b', 'ab', 'ba', 'example', 'xample', 'org', ''];
        $matchDomain = new ReflectionMethod(DomainContext::class, 'matchDomain');
        $context = new DomainContext();

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $host = $this->join($labels, $this->random->getInt(1, 4));
            $domain = $this->join($labels, $this->random->getInt(1, 3));
            if ($this->random->getInt(0, 3) === 0) {
                // Derive the host from the domain so that matches occur.
                $host = $this->join($labels, $this->random->getInt(0, 2)) . '.' . $domain;
            }

            $pattern = '.' . $domain;
            $hostLabels = explode('.', $host);
            $domainLabels = explode('.', $domain);
            $expected = \count($hostLabels) >= \count($domainLabels)
                && \array_slice($hostLabels, -\count($domainLabels)) === $domainLabels;

            self::assertSame(
                $expected,
                $matchDomain->invoke($context, $pattern, $host),
                \sprintf('Pattern %s against host %s', json_encode($pattern), json_encode($host)),
            );
        }
    }

    #[Test]
    public function ipRangesAreComparedWithoutErrors(): void
    {
        $parts = ['192', '168', '10', '0', '1', '255', '256', '-1', '.', '.', ':', '::', '/', '/8', '/33', '*', ',', ' ',
            'fe80', 'ffff', 'g', ''];
        $isIpInRange = new ReflectionMethod(IpContext::class, 'isIpInRange');
        $context = new IpContext();
        $addresses = ['192.168.1.1', '10.0.0.1', '::1', 'fe80::1', '2001:db8::ff00:42:8329'];

        foreach ($this->inputs('ip', $parts, 10) as $range) {
            foreach ($addresses as $address) {
                $isIpv4 = filter_var($address, \FILTER_VALIDATE_IP, \FILTER_FLAG_IPV4) !== false;

                try {
                    $result = $isIpInRange->invoke($context, $address, $isIpv4, $range);
                } catch (Throwable $e) {
                    self::fail(\sprintf(
                        'Address %s, range %s: %s: %s',
                        $address,
                        json_encode($range),
                        $e::class,
                        $e->getMessage(),
                    ));
                }

                self::assertIsBool($result);
            }
        }
    }

    /**
     * The seed corpus followed by random concatenations of the given parts.
     *
     * @param list<string> $parts
     *
     * @return list<string>
     */
    private function inputs(string $corpus, array $parts, int $maxParts): array
    {
        $inputs = [];
        $files = glob(__DIR__ . '/corpus/' . $corpus . '/*');
        foreach ($files === false ? [] : $files as $file) {
            $inputs[] = trim((string) file_get_contents($file));
        }

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $input = '';
            for ($n = $this->random->getInt(0, $maxParts); $n > 0; --$n) {
                $input .= $parts[$this->random->getInt(0, \count($parts) - 1)];
            }

            $inputs[] = $input;
        }

        return $inputs;
    }

    /**
     * @param list<string> $labels
     */
    private function join(array $labels, int $count): string
    {
        $picked = [];
        for ($i = 0; $i < $count; ++$i) {
            $picked[] = $labels[$this->random->getInt(0, \count($labels) - 1)];
        }

        return implode('.', $picked);
    }
}
