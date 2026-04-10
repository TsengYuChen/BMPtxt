<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Services;

use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Config\Bmptxt as BmptxtConfig;
use TsengYuChen\BMPtxt\BmpConverter;

/**
 * CodeIgniter 4 Service factory for BMPtxt.
 *
 * To register in your app/Config/Services.php, add:
 *
 *   use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Services\BmptxtService;
 *
 *   public static function bmptxt(bool $getShared = true): \TsengYuChen\BMPtxt\BmpConverter
 *   {
 *       if ($getShared) {
 *           return static::getSharedInstance('bmptxt');
 *       }
 *       return BmptxtService::make();
 *   }
 *
 * Usage in controllers:
 *   $hex = service('bmptxt')
 *       ->text('薄型 SMCG TVS 無鹵')
 *       ->size(12)
 *       ->toEzpl()
 *       ->toHex();
 */
class BmptxtService
{
    private static ?BmpConverter $instance = null;

    /**
     * Create or return a shared BmpConverter instance, configured from CI4 config.
     *
     * @param BmptxtConfig|null $config Pass null to auto-load from config('Bmptxt')
     */
    public static function make(?BmptxtConfig $config = null): BmpConverter
    {
        if (static::$instance === null) {
            /** @var BmptxtConfig $config */
            $config ??= config('Bmptxt') ?? new BmptxtConfig();
            static::$instance = BmpConverter::create($config->fontsPath);
        }

        return static::$instance;
    }

    /**
     * Reset the shared instance (useful for testing).
     */
    public static function reset(): void
    {
        static::$instance = null;
    }
}
