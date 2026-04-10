<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Tests\Unit\Support;

use TsengYuChen\BMPtxt\Support\IpChecker;
use TsengYuChen\BMPtxt\Tests\TestCase;

class IpCheckerTest extends TestCase
{
    // ─── Exact IP matching ────────────────────────────────────────────────────

    public function test_exact_ip_match(): void
    {
        $this->assertTrue(IpChecker::matches('127.0.0.1', '127.0.0.1'));
    }

    public function test_exact_ip_no_match(): void
    {
        $this->assertFalse(IpChecker::matches('192.168.1.1', '192.168.1.2'));
    }

    // ─── CIDR matching ────────────────────────────────────────────────────────

    public function test_cidr_192_168_matches(): void
    {
        $this->assertTrue(IpChecker::matches('192.168.1.100', '192.168.0.0/16'));
        $this->assertTrue(IpChecker::matches('192.168.99.1',  '192.168.0.0/16'));
    }

    public function test_cidr_192_168_no_match_different_prefix(): void
    {
        $this->assertFalse(IpChecker::matches('192.169.1.1', '192.168.0.0/16'));
    }

    public function test_cidr_10_0_0_0_slash8(): void
    {
        $this->assertTrue(IpChecker::matches('10.0.0.1',  '10.0.0.0/8'));
        $this->assertTrue(IpChecker::matches('10.255.255.255', '10.0.0.0/8'));
    }

    public function test_cidr_10_no_match_external(): void
    {
        $this->assertFalse(IpChecker::matches('11.0.0.1', '10.0.0.0/8'));
    }

    public function test_cidr_slash32_is_exact_match(): void
    {
        $this->assertTrue(IpChecker::matches('192.168.1.5', '192.168.1.5/32'));
        $this->assertFalse(IpChecker::matches('192.168.1.6', '192.168.1.5/32'));
    }

    public function test_invalid_ip_does_not_match(): void
    {
        $this->assertFalse(IpChecker::matches('not-an-ip', '192.168.0.0/16'));
    }

    // ─── isAllowed() ─────────────────────────────────────────────────────────

    public function test_is_allowed_with_multiple_cidrs(): void
    {
        $allowedIps = ['127.0.0.1', '::1', '192.168.0.0/16', '10.0.0.0/8'];

        $this->assertTrue(IpChecker::isAllowed('127.0.0.1',   $allowedIps));
        $this->assertTrue(IpChecker::isAllowed('192.168.5.5', $allowedIps));
        $this->assertTrue(IpChecker::isAllowed('10.1.2.3',    $allowedIps));
    }

    public function test_external_ip_is_not_allowed(): void
    {
        $allowedIps = ['127.0.0.1', '192.168.0.0/16', '10.0.0.0/8'];

        $this->assertFalse(IpChecker::isAllowed('8.8.8.8',     $allowedIps));
        $this->assertFalse(IpChecker::isAllowed('203.0.113.1', $allowedIps));
    }

    public function test_empty_allowed_list_denies_all(): void
    {
        $this->assertFalse(IpChecker::isAllowed('127.0.0.1', []));
    }
}
