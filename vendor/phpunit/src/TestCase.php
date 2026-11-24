<?php
namespace PHPUnit\Framework;

/**
 * STANDALONE PHPUNIT TESTCASE COMPATIBILITY LAYER
 * Menyediakan fungsionalitas inti PHPUnit TestCase tanpa perlu koneksi internet.
 */
abstract class TestCase
{
    private static int $assertionCount = 0;

    public static function getAssertionCount(): int
    {
        return self::$assertionCount;
    }

    public static function resetAssertionCount(): void
    {
        self::$assertionCount = 0;
    }

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    public static function assertTrue(mixed $condition, string $message = ''): void
    {
        self::$assertionCount++;
        if ($condition !== true) {
            throw new \Exception($message ?: "Failed asserting that false is true.");
        }
    }

    public static function assertFalse(mixed $condition, string $message = ''): void
    {
        self::$assertionCount++;
        if ($condition !== false) {
            throw new \Exception($message ?: "Failed asserting that true is false.");
        }
    }

    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($expected != $actual) {
            throw new \Exception($message ?: sprintf("Failed asserting that %s matches expected %s.", json_encode($actual), json_encode($expected)));
        }
    }

    public static function assertSame(mixed $expected, mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($expected !== $actual) {
            throw new \Exception($message ?: sprintf("Failed asserting that %s is identical to %s.", json_encode($actual), json_encode($expected)));
        }
    }

    public static function assertNull(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual !== null) {
            throw new \Exception($message ?: "Failed asserting that value is null.");
        }
    }

    public static function assertNotNull(mixed $actual, string $message = ''): void
    {
        self::$assertionCount++;
        if ($actual === null) {
            throw new \Exception($message ?: "Failed asserting that value is not null.");
        }
    }

    public static function assertCount(int $expectedCount, mixed $haystack, string $message = ''): void
    {
        self::$assertionCount++;
        $actualCount = is_countable($haystack) ? count($haystack) : 0;
        if ($expectedCount !== $actualCount) {
            throw new \Exception($message ?: "Failed asserting that count is {$expectedCount}, actual {$actualCount}.");
        }
    }
}
