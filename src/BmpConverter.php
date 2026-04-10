<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt;

use TsengYuChen\BMPtxt\Core\Converter\GdToEzplConverter;
use TsengYuChen\BMPtxt\Core\Font\FontManager;
use TsengYuChen\BMPtxt\Core\Renderer\ImageRenderer;
use TsengYuChen\BMPtxt\Core\Renderer\TextRenderer;

/**
 * Main entry point for the BMPtxt package.
 *
 * Provides a simple factory API for creating text and image renderers,
 * with pre-wired dependencies (FontManager, GdToEzplConverter).
 *
 * Usage (plain PHP):
 *   $converter = new BmpConverter();
 *
 *   // Text → EZPL
 *   $result = $converter->text('薄型 SMCG TVS 無鹵')
 *       ->font(\TsengYuChen\BMPtxt\Enums\FontFamily::KAIU)
 *       ->size(12)
 *       ->dpi(232)
 *       ->toEzpl(x: 0, y: 0);
 *
 *   echo $result->toHex();      // hex string for HTTP response
 *   echo $result->toBinary();   // binary for socket_send()
 *
 *   // Image → BW EZPL
 *   $result = $converter->image()
 *       ->fromUrl('http://192.168.1.10/logo.png')
 *       ->scale(1.5)
 *       ->blackWhite(0.8)
 *       ->toEzpl(x: 100, y: 120);
 *
 * Usage (static factory):
 *   $converter = BmpConverter::create('/path/to/custom/fonts');
 */
class BmpConverter
{
    private readonly FontManager       $fontManager;
    private readonly GdToEzplConverter $gdConverter;
    private ?\Psr\SimpleCache\CacheInterface $cache = null;
    private ?int $cacheTtl = null;

    public function __construct(
        ?FontManager       $fontManager = null,
        ?GdToEzplConverter $gdConverter = null,
    ) {
        $this->fontManager = $fontManager ?? new FontManager();
        $this->gdConverter = $gdConverter ?? new GdToEzplConverter();
    }

    /**
     * Set a PSR-16 simple cache instance to be used by all renderers created by this converter.
     *
     * @param int|null $ttl Time-to-live in seconds
     */
    public function withCache(\Psr\SimpleCache\CacheInterface $cache, ?int $ttl = null): static
    {
        $this->cache = $cache;
        $this->cacheTtl = $ttl;
        return $this;
    }

    /**
     * Create a TextRenderer pre-configured with optional content.
     *
     * @param string $content Optional text content (can also be set via ->content())
     */
    public function text(string $content = ''): TextRenderer
    {
        $renderer = new TextRenderer($this->fontManager, $this->gdConverter);

        if ($this->cache !== null) {
            $renderer = $renderer->withCache($this->cache, $this->cacheTtl);
        }

        if ($content !== '') {
            return $renderer->content($content);
        }

        return $renderer;
    }

    /**
     * Create an ImageRenderer ready to be configured.
     */
    public function image(): ImageRenderer
    {
        $renderer = new ImageRenderer($this->gdConverter);

        if ($this->cache !== null) {
            $renderer = $renderer->withCache($this->cache, $this->cacheTtl);
        }

        return $renderer;
    }

    /**
     * Get the FontManager used by this converter instance.
     * Useful for querying available fonts.
     */
    public function getFontManager(): FontManager
    {
        return $this->fontManager;
    }

    // ─── Static factory ───────────────────────────────────────────────────────

    /**
     * Create a BmpConverter with an optional custom fonts directory.
     *
     * @param string|null $fontsPath Absolute path to a directory containing font files.
     *                               Defaults to the package's bundled fonts/ directory.
     */
    public static function create(?string $fontsPath = null): static
    {
        $fontManager = new FontManager($fontsPath);
        return new static($fontManager);
    }
}
