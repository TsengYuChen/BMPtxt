<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use TsengYuChen\BMPtxt\BmpConverter;
use TsengYuChen\BMPtxt\Core\Font\FontManager;
use TsengYuChen\BMPtxt\Core\Renderer\ImageRenderer;
use TsengYuChen\BMPtxt\Core\Renderer\TextRenderer;

/**
 * Laravel Facade for BmpConverter.
 *
 * Usage:
 *   use TsengYuChen\BMPtxt\Adapters\Laravel\Facades\Bmptxt;
 *
 *   $hex = Bmptxt::text('薄型 SMCG TVS 無鹵')
 *       ->size(12)
 *       ->toEzpl(x: 0, y: 0)
 *       ->toHex();
 *
 *   $hex = Bmptxt::image()
 *       ->fromUrl('http://192.168.1.10/logo.png')
 *       ->blackWhite()
 *       ->toEzpl()
 *       ->toHex();
 *
 * @method static TextRenderer  text(string $content = '')
 * @method static ImageRenderer image()
 * @method static FontManager   getFontManager()
 *
 * @see BmpConverter
 */
class Bmptxt extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'bmptxt';
    }
}
