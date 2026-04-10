<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Absolute path to the package's bundled fonts directory.
     */
    protected function fontsPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'fonts';
    }

    /**
     * Absolute path to the tests/fixtures directory.
     */
    protected function fixturesPath(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'fixtures';
    }

    /**
     * Assert that a string contains only printable ASCII or binary bytes.
     */
    protected function assertIsBinaryString(string $data, string $message = ''): void
    {
        $this->assertNotEmpty($data, $message ?: 'Binary data should not be empty.');
    }
}
