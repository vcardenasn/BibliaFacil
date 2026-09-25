<?php

class TestCase
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(string $name, callable $test): void
    {
        try {
            $test();
            $this->passed++;
            echo "PASS {$name}\n";
        } catch (Throwable $exception) {
            $this->failed++;
            echo "FAIL {$name}: {$exception->getMessage()}\n";
        }
    }

    public function assertTrue(bool $condition, string $message = 'Expected true.'): void
    {
        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    public function assertFalse(bool $condition, string $message = 'Expected false.'): void
    {
        $this->assertTrue(!$condition, $message);
    }

    public function assertSame(mixed $expected, mixed $actual): void
    {
        if ($expected !== $actual) {
            throw new RuntimeException(
                'Expected ' . var_export($expected, true) . ', got ' . var_export($actual, true) . '.'
            );
        }
    }

    public function assertNull(mixed $actual): void
    {
        $this->assertSame(null, $actual);
    }

    public function assertThrows(string $exceptionClass, callable $action): void
    {
        try {
            $action();
        } catch (Throwable $exception) {
            if ($exception instanceof $exceptionClass) {
                return;
            }
            throw new RuntimeException('Expected ' . $exceptionClass . ', got ' . $exception::class . '.');
        }
        throw new RuntimeException('Expected ' . $exceptionClass . ' to be thrown.');
    }

    public function finish(): int
    {
        echo "\n{$this->passed} passed, {$this->failed} failed.\n";
        return $this->failed === 0 ? 0 : 1;
    }
}
