<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Support;

/**
 * Shared IP address / CIDR range checking utility.
 *
 * Used by all framework adapters (Laravel, Yii2, CodeIgniter 4)
 * to implement the internal-IP-only restriction that the original
 * codebase enforced via is_external().
 */
class IpChecker
{
    /**
     * Check if an IP address is allowed by any of the given CIDR ranges or exact IPs.
     *
     * @param string   $ip           Client IP address (IPv4)
     * @param string[] $allowedCidrs List of allowed IPs or CIDR ranges
     *                               e.g. ['127.0.0.1', '192.168.0.0/16', '10.0.0.0/8']
     */
    public static function isAllowed(string $ip, array $allowedCidrs): bool
    {
        foreach ($allowedCidrs as $cidr) {
            if (self::matches($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if a single IP matches an exact IP or CIDR range.
     */
    public static function matches(string $ip, string $cidr): bool
    {
        // Exact match
        if (!str_contains($cidr, '/')) {
            return $ip === $cidr;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);

        $ipLong     = ip2long($ip);
        $subnetLong = ip2long($subnet);

        // Invalid IP or subnet → no match
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = (int) $bits === 0 ? 0 : (~0 << (32 - (int) $bits));

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
