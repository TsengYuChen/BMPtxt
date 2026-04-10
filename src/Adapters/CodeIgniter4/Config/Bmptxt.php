<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * CodeIgniter 4 configuration for BMPtxt.
 *
 * Publish this file to your app with:
 *   php spark vendor:publish (if supported)
 *   or copy manually to app/Config/Bmptxt.php and change the namespace to Config
 *
 * Usage in app/Config/Bmptxt.php:
 *   namespace Config;
 *   use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Config\Bmptxt as BMPtxtConfig;
 *   class Bmptxt extends BMPtxtConfig {}
 */
class Bmptxt extends BaseConfig
{
    /**
     * Absolute path to fonts directory.
     * null = use the bundled fonts inside the package (fonts/).
     */
    public ?string $fontsPath = null;

    /**
     * Default output DPI for label printers.
     * GODEX label printers typically use 203 or 232 DPI.
     */
    public int $defaultDpi = 232;

    /**
     * Allowed IPs or CIDR ranges for internal-only access.
     * Used by InternalIpFilter to replicate original is_external() logic.
     *
     * @var string[]
     */
    public array $allowedIps = [
        '127.0.0.1',
        '::1',
        '192.168.0.0/16',
        '10.0.0.0/8',
    ];
}
