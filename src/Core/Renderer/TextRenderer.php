<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Renderer;

use GdImage;
use TsengYuChen\BMPtxt\Contracts\EzplOutputInterface;
use TsengYuChen\BMPtxt\Core\Converter\GdToEzplConverter;
use TsengYuChen\BMPtxt\Core\Font\FontManager;
use TsengYuChen\BMPtxt\Enums\FontFamily;
use TsengYuChen\BMPtxt\Exceptions\RenderException;
use TsengYuChen\BMPtxt\ValueObjects\EzplResult;

/**
 * Renders text (TTF/OTF font) to a GD image and converts to EZPL output.
 *
 * Implements an immutable fluent interface — each setter returns a new
 * clone so the same renderer instance can be reused safely.
 *
 * Usage:
 *   $result = (new TextRenderer($fontManager))
 *       ->content('薄型 SMCG TVS')
 *       ->font(FontFamily::KAIU)
 *       ->size(12)
 *       ->dpi(232)
 *       ->toEzpl(x: 0, y: 0);
 */
class TextRenderer implements EzplOutputInterface
{
    /** Text content to render */
    private string $text = '';

    /** Font size in pt (screen pt, will be scaled to printer DPI) */
    private float $size = 12.0;

    /** Target printer DPI (default: GODEX label printer standard) */
    private int $exportDpi = 232;

    /** Screen DPI baseline for point-to-pixel conversion */
    private int $screenDpi = 72;

    /** Font family to use */
    private FontFamily $font = FontFamily::KAIU;

    /**
     * Pixel padding around the text bounding box.
     * Extra right padding (×10) compensates for imageftbbox under-estimation.
     */
    private int $padding = 5;

    private ?\Psr\SimpleCache\CacheInterface $cache = null;
    private ?int $cacheTtl = null;

    public function __construct(
        private readonly FontManager       $fontManager,
        private readonly GdToEzplConverter $converter = new GdToEzplConverter(),
    ) {}

    // ─── Fluent setters (immutable) ──────────────────────────────────────────

    /**
     * Set the text content.
     * URL-encoded strings are automatically decoded.
     */
    public function content(string $text): static
    {
        $clone = clone $this;
        $clone->text = urldecode($text);
        return $clone;
    }

    /**
     * Set the font size in points (pt).
     */
    public function size(float $size): static
    {
        $clone = clone $this;
        $clone->size = $size;
        return $clone;
    }

    /**
     * Set the printer output DPI.
     * This scales the font size so it appears correct on the physical label.
     */
    public function dpi(int $dpi): static
    {
        $clone = clone $this;
        $clone->exportDpi = $dpi;
        return $clone;
    }

    /**
     * Set the font family to use.
     */
    public function font(FontFamily $font): static
    {
        $clone = clone $this;
        $clone->font = $font;
        return $clone;
    }

    /**
     * Attach a PSR-16 cache pool to cache the rendering result.
     *
     * @param \Psr\SimpleCache\CacheInterface $cache The cache pool
     * @param int|null                        $ttl   Time-to-live in seconds
     */
    public function withCache(\Psr\SimpleCache\CacheInterface $cache, ?int $ttl = null): static
    {
        $clone = clone $this;
        $clone->cache = $cache;
        $clone->cacheTtl = $ttl;
        return $clone;
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    /**
     * Render the text to a GD image resource.
     * The caller is responsible for calling imagedestroy() on the result.
     *
     * @throws RenderException if text is empty or GD fails
     */
    public function render(): GdImage
    {
        if (empty(trim($this->text))) {
            throw new RenderException('Text content cannot be empty.');
        }

        $fontPath   = $this->fontManager->getPath($this->font);
        // Scale point size from screen DPI to printer DPI
        $actualSize = $this->size * ($this->exportDpi / $this->screenDpi);

        // Measure bounding box at angle 0
        $bbox = imageftbbox($actualSize, 0, $fontPath, $this->text);
        if ($bbox === false) {
            throw new RenderException(
                "imageftbbox() failed for font [{$this->font->value}]. "
                . "Ensure the font file is a valid TTF/OTF and PHP GD freetype support is enabled."
            );
        }

        /**
         * imageftbbox() returns 8 coordinates (lower-left, lower-right, upper-right, upper-left):
         *   0: LL-x  1: LL-y  2: LR-x  3: LR-y  4: UR-x  5: UR-y  6: UL-x  7: UL-y
         */
        $p       = $this->padding;
        $width   = abs($bbox[2] - $bbox[0]) + $p * 2 + $p * 10; // extra right padding for safety
        $height  = abs($bbox[5] - $bbox[3]) + $p * 2;
        $textX   = $p;
        $textY   = $height - $bbox[1] - 1 - $p;

        $im = imagecreatetruecolor($width, $height);
        if ($im === false) {
            throw new RenderException('imagecreatetruecolor() failed.');
        }

        $black = imagecolorallocate($im, 0, 0, 0);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $white);
        imagefttext($im, $actualSize, 0, $textX, $textY, $black, $fontPath, $this->text);

        return $im;
    }

    /**
     * Render the text and return an EZPL result.
     * GD memory is freed automatically after conversion.
     *
     * @param int $x X position on label (in dots)
     * @param int $y Y position on label (in dots)
     */
    public function toEzpl(int $x = 0, int $y = 0): EzplResult
    {
        $cacheKey = '';
        if ($this->cache !== null) {
            $cacheKey = 'bmptxt:text:' . md5($this->text . $this->font->value . $this->size . $this->exportDpi . $x . $y);
            $cached = $this->cache->get($cacheKey);
            if ($cached instanceof EzplResult) {
                return $cached;
            }
        }

        $im     = $this->render();
        $result = $this->converter->convert($im, $x, $y);
        imagedestroy($im);

        if ($this->cache !== null && $cacheKey !== '') {
            $this->cache->set($cacheKey, $result, $this->cacheTtl);
        }

        return $result;
    }

    /**
     * Render the text and return a RAW result string.
     *
     * Format: "{widthBytes},{heightLines},{decimal_byte_data}"
     */
    public function toRaw(): string
    {
        return $this->toEzpl()->toRaw();
    }
}
