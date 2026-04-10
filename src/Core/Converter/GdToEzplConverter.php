<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Converter;

use GdImage;
use TsengYuChen\BMPtxt\Exceptions\RenderException;
use TsengYuChen\BMPtxt\ValueObjects\EzplResult;

/**
 * Converts a GD image resource directly to EZPL binary bitmap data.
 *
 * This is the core algorithm of the package. It replaces the original
 * phpThumb → BMP string → BMP parse → EZPL pipeline with a direct
 * GD pixel-reading approach, eliminating the BMP intermediate step.
 *
 * EZPL bitmap format:
 *   - Each pixel row is packed into bytes (8 pixels per byte)
 *   - Black pixel (dark) = bit 1
 *   - White pixel (light) = bit 0
 *   - Row width is padded to the nearest byte boundary
 */
class GdToEzplConverter
{
    /**
     * Convert a GD image to black-and-white in place.
     *
     * Uses GD's built-in IMG_FILTER_GRAYSCALE for speed,
     * then applies a threshold to force pure black/white.
     *
     * @param GdImage $im        Source GD image (modified in place)
     * @param float   $threshold Brightness cutoff, range 0.0–1.0.
     *                           Pixels with brightness < threshold*256 become black.
     *                           Default 0.8 matches the original codebase.
     * @return GdImage The same image, now in pure black/white
     */
    public function toBlackWhite(GdImage $im, float $threshold = 0.8): GdImage
    {
        // GD's native grayscale filter is C-level fast; much faster than looping in PHP
        imagefilter($im, IMG_FILTER_GRAYSCALE);

        $cutoff = (int) (256 * $threshold);
        $width  = imagesx($im);
        $height = imagesy($im);

        // After IMG_FILTER_GRAYSCALE, R == G == B, so reading any channel suffices
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                // imagecolorat returns a packed int; after grayscale the low byte is the gray value
                $gray  = imagecolorat($im, $x, $y) & 0xFF;
                $color = $gray < $cutoff ? 0x000000 : 0xFFFFFF;
                imagesetpixel($im, $x, $y, $color);
            }
        }

        return $im;
    }

    /**
     * Convert a GD image (already black/white) to EZPL binary data.
     *
     * Reads pixels directly from GD — no BMP intermediate needed.
     *
     * @param GdImage $im Source GD image (expected to already be black/white)
     * @param int     $x  X position on label (in dots)
     * @param int     $y  Y position on label (in dots)
     *
     * @throws RenderException if the image is empty
     */
    public function convert(GdImage $im, int $x = 0, int $y = 0): EzplResult
    {
        $width  = imagesx($im);
        $height = imagesy($im);

        if ($width === 0 || $height === 0) {
            throw new RenderException('Cannot convert an empty GD image to EZPL.');
        }

        $widthBytes = (int) ceil($width / 8);
        $paddingBits = ($widthBytes * 8) - $width; // right-padding bits to fill last byte

        $data = '';

        for ($row = 0; $row < $height; $row++) {
            $bits = '';
            for ($col = 0; $col < $width; $col++) {
                // After toBlackWhite(), pixels are exactly 0x000000 or 0xFFFFFF
                // The low byte of imagecolorat() on a pure-black pixel is 0
                $pixel = imagecolorat($im, $col, $row) & 0xFF;
                $bits .= $pixel === 0 ? '1' : '0'; // black=1 (print), white=0 (no print)

                // Pack every 8 bits into one byte
                if (strlen($bits) === 8) {
                    $data .= chr((int) base_convert($bits, 2, 10));
                    $bits  = '';
                }
            }

            // Pad the last (possibly partial) byte with zeros on the right
            if ($bits !== '') {
                $bits  = str_pad($bits, 8, '0', STR_PAD_RIGHT);
                $data .= chr((int) base_convert($bits, 2, 10));
            }
        }

        return new EzplResult(
            data:        $data,
            widthBytes:  $widthBytes,
            heightLines: $height,
            x:           $x,
            y:           $y,
        );
    }
}
