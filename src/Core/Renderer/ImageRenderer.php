<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Renderer;

use GdImage;
use TsengYuChen\BMPtxt\Contracts\EzplOutputInterface;
use TsengYuChen\BMPtxt\Core\Converter\GdToEzplConverter;
use TsengYuChen\BMPtxt\Exceptions\ImageFetchException;
use TsengYuChen\BMPtxt\Exceptions\RenderException;
use TsengYuChen\BMPtxt\ValueObjects\EzplResult;

/**
 * Fetches an image (from URL or file path), applies transformations,
 * converts to black/white, and outputs EZPL bitmap data.
 *
 * Replaces phpThumb with PHP's native imagecreatefromstring(),
 * which auto-detects JPEG, PNG, GIF, BMP, WebP, AVIF, etc.
 *
 * Implements an immutable fluent interface.
 *
 * Usage:
 *   $result = (new ImageRenderer())
 *       ->fromUrl('http://192.168.9.72/logo.png')
 *       ->scale(1.5)
 *       ->rotate(10)
 *       ->blackWhite(threshold: 0.8)
 *       ->toEzpl(x: 100, y: 120);
 */
class ImageRenderer implements EzplOutputInterface
{
    private ?string $source    = null;
    private float   $scale     = 1.0;
    private float   $rotateDeg = 0.0;
    private bool    $applyBW   = true;
    private float   $bwThreshold = 0.8;

    /** HTTP fetch timeout in seconds */
    private int $fetchTimeout = 10;

    private ?\Psr\SimpleCache\CacheInterface $cache = null;
    private ?int $cacheTtl = null;

    public function __construct(
        private readonly GdToEzplConverter $converter = new GdToEzplConverter(),
    ) {}

    // ─── Fluent setters (immutable) ──────────────────────────────────────────

    /**
     * Set the image source to a remote URL.
     * SSL certificate verification is intentionally disabled for internal network use.
     */
    public function fromUrl(string $url): static
    {
        $clone = clone $this;
        $clone->source = $url;
        return $clone;
    }

    /**
     * Set the image source to a local file path.
     */
    public function fromFile(string $path): static
    {
        $clone = clone $this;
        $clone->source = $path;
        return $clone;
    }

    /**
     * Scale the image by a multiplier.
     * e.g. scale(1.5) → 150% of original size.
     */
    public function scale(float $scale): static
    {
        $clone = clone $this;
        $clone->scale = $scale;
        return $clone;
    }

    /**
     * Rotate the image by the given degrees (clockwise).
     */
    public function rotate(float $degrees): static
    {
        $clone = clone $this;
        $clone->rotateDeg = $degrees;
        return $clone;
    }

    /**
     * Enable black/white conversion with the given threshold.
     *
     * @param float $threshold Brightness cutoff 0.0–1.0. Pixels below this are black.
     */
    public function blackWhite(float $threshold = 0.8): static
    {
        $clone = clone $this;
        $clone->applyBW     = true;
        $clone->bwThreshold = $threshold;
        return $clone;
    }

    /**
     * Set the HTTP fetch timeout (seconds).
     */
    public function timeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->fetchTimeout = $seconds;
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
     * Fetch and process the image, returning a GD resource.
     * The caller is responsible for calling imagedestroy().
     *
     * @throws RenderException      if source is not set or transformation fails
     * @throws ImageFetchException  if the image cannot be fetched or decoded
     */
    public function render(): GdImage
    {
        if ($this->source === null) {
            throw new RenderException(
                'Image source is not set. Call fromUrl() or fromFile() first.'
            );
        }

        $raw = $this->fetchRaw($this->source);

        $im = @imagecreatefromstring($raw);
        if ($im === false) {
            throw new ImageFetchException(
                "GD could not decode image from [{$this->source}]. "
                . "Supported formats: JPEG, PNG, GIF, BMP, WebP, AVIF."
            );
        }

        // ── Scale ─────────────────────────────────────────────────────────
        if ($this->scale !== 1.0) {
            $newW   = (int) round(imagesx($im) * $this->scale);
            $newH   = (int) round(imagesy($im) * $this->scale);
            $scaled = imagescale($im, $newW, $newH, IMG_BICUBIC);
            imagedestroy($im);
            if ($scaled === false) {
                throw new RenderException("imagescale() failed (target: {$newW}×{$newH}).");
            }
            $im = $scaled;
        }

        // ── Rotate ────────────────────────────────────────────────────────
        if ($this->rotateDeg !== 0.0) {
            $white   = imagecolorallocate($im, 255, 255, 255);
            $rotated = imagerotate($im, $this->rotateDeg, $white);
            imagedestroy($im);
            if ($rotated === false) {
                throw new RenderException("imagerotate() failed (angle: {$this->rotateDeg}°).");
            }
            $im = $rotated;
        }

        // ── Black & White ────────────────────────────────────────────────
        if ($this->applyBW) {
            $im = $this->converter->toBlackWhite($im, $this->bwThreshold);
        }

        return $im;
    }

    /**
     * Render the image and return an EZPL result.
     * GD memory is freed automatically after conversion.
     *
     * @param int $x X position on label (in dots)
     * @param int $y Y position on label (in dots)
     */
    public function toEzpl(int $x = 0, int $y = 0): EzplResult
    {
        $cacheKey = '';
        if ($this->cache !== null) {
            $sourceKey = $this->source ?? '';
            $cacheKey  = 'bmptxt:image:' . md5($sourceKey . $this->scale . $this->rotateDeg . (int)$this->applyBW . $this->bwThreshold . $x . $y);
            $cached    = $this->cache->get($cacheKey);
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

    // ─── Private helpers ─────────────────────────────────────────────────────

    /**
     * Fetch raw image bytes from a URL or local file path.
     *
     * @throws ImageFetchException on failure
     */
    private function fetchRaw(string $source): string
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'timeout' => $this->fetchTimeout,
            ],
        ]);

        $raw = @file_get_contents($source, false, $context);

        if ($raw === false || $raw === '') {
            throw new ImageFetchException(
                "Failed to fetch image from [{$source}]. "
                . "Ensure the URL is reachable or the file path exists."
            );
        }

        return $raw;
    }
}
