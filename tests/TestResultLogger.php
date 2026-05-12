<?php

namespace Tests;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use Throwable;

class TestResultLogger implements TestListener
{
    use TestListenerDefaultImplementation;

    private string $logFile;
    private float $suiteStart;
    private array $lines = [];
    private int $passed = 0;
    private int $failed = 0;
    private int $errors = 0;

    public function __construct()
    {
        $this->logFile = dirname(__DIR__) . '/storage/logs/phpunit-results.txt';
        $this->suiteStart = microtime(true);

        $header = str_repeat('=', 60) . PHP_EOL
            . 'Test Run: ' . date('Y-m-d H:i:s') . PHP_EOL
            . str_repeat('=', 60) . PHP_EOL;

        file_put_contents($this->logFile, $header);
    }

    public function endTest(Test $test, float $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        $this->passed++;
        $this->lines[] = sprintf('  [PASS] %s (%.3fs)', $test->getName(), $time);
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        $this->failed++;
        $this->lines[] = sprintf('  [FAIL] %s (%.3fs)', $test->getName(), $time);
        $this->lines[] = sprintf('         → %s', $e->getMessage());
    }

    public function addError(Test $test, Throwable $t, float $time): void
    {
        if (!$test instanceof TestCase) {
            return;
        }

        $this->errors++;
        $this->lines[] = sprintf('  [ERROR] %s (%.3fs)', $test->getName(), $time);
        $this->lines[] = sprintf('          → %s', $t->getMessage());
    }

    public function startTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() === '') {
            return;
        }

        $this->lines[] = PHP_EOL . $suite->getName() . ':';
    }

    public function endTestSuite(TestSuite $suite): void
    {
        if ($suite->getName() !== '' && !empty($this->lines)) {
            file_put_contents($this->logFile, implode(PHP_EOL, $this->lines) . PHP_EOL, FILE_APPEND);
            $this->lines = [];
        }

        $isRoot = $suite->getName() === '' || str_ends_with($suite->getName(), '.php');
        if ($isRoot && ($this->passed + $this->failed + $this->errors) > 0) {
            $duration = round(microtime(true) - $this->suiteStart, 3);
            $summary = PHP_EOL . str_repeat('-', 60) . PHP_EOL
                . sprintf(
                    'Result: %d passed, %d failed, %d errors  |  %.3fs%s',
                    $this->passed,
                    $this->failed,
                    $this->errors,
                    $duration,
                    PHP_EOL
                )
                . str_repeat('=', 60) . PHP_EOL;

            file_put_contents($this->logFile, $summary, FILE_APPEND);
        }
    }
}
