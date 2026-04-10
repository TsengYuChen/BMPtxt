<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Parser;

use TsengYuChen\BMPtxt\Exceptions\BmpParserException;

/**
 * Parses BMP (Bitmap) binary data and extracts header metadata and pixel arrays.
 *
 * Supports standard 24-bit Windows BMP format (BITMAPINFOHEADER).
 * Uses PHP's unpack() with little-endian format specifiers for robust
 * multi-byte integer reading, replacing the original hex string manipulation.
 *
 * This class is kept for scenarios that require direct BMP file parsing.
 * For GD-to-EZPL conversion, use GdToEzplConverter instead.
 */
class BmpParser
{
    /** Minimum BMP header size (DIB header starts at byte 14, BITMAPINFOHEADER is 40 bytes) */
    private const MIN_HEADER_SIZE = 54;

    /**
     * Parse a BMP binary string into structured data.
     *
     * @param  string $blob Raw BMP binary data
     * @return array{
     *     type: string,
     *     file_size: int,
     *     image_data_offset: int,
     *     bitmap_info_header_size: int,
     *     width: int,
     *     height: int,
     *     planes: int,
     *     bit_pixel: int,
     *     compression: int,
     *     image_size: int,
     *     h_resolution: int,
     *     v_resolution: int,
     *     used_color: int,
     *     important_color: int,
     *     hex: array<int, array<int, string>>
     * }
     * @throws BmpParserException on invalid BMP data
     */
    public function parse(string $blob): array
    {
        if (strlen($blob) < self::MIN_HEADER_SIZE) {
            throw new BmpParserException(
                sprintf(
                    'Invalid BMP: data is %d bytes, minimum required is %d bytes.',
                    strlen($blob),
                    self::MIN_HEADER_SIZE
                )
            );
        }

        $type = substr($blob, 0, 2);
        if ($type !== 'BM') {
            throw new BmpParserException(
                "Invalid BMP signature: expected 'BM', got '" . addslashes($type) . "'."
            );
        }

        // Parse header fields using unpack() with little-endian specifiers
        // 'V' = unsigned 32-bit LE, 'v' = unsigned 16-bit LE
        $r = [
            'type'                    => $type,
            'file_size'               => $this->readU32($blob, 2),
            // bytes 6–9 are reserved (skip)
            'image_data_offset'       => $this->readU32($blob, 10),
            'bitmap_info_header_size' => $this->readU32($blob, 14),
            'width'                   => $this->readU32($blob, 18),
            'height'                  => $this->readU32($blob, 22),
            'planes'                  => $this->readU16($blob, 26),
            'bit_pixel'               => $this->readU16($blob, 28),
            'compression'             => $this->readU32($blob, 30),
            'image_size'              => $this->readU32($blob, 34),
            'h_resolution'            => $this->readU32($blob, 38),
            'v_resolution'            => $this->readU32($blob, 42),
            'used_color'              => $this->readU32($blob, 46),
            'important_color'         => $this->readU32($blob, 50),
        ];

        if ($r['bit_pixel'] !== 24) {
            throw new BmpParserException(
                "Unsupported BMP color depth: {$r['bit_pixel']} bpp. Only 24-bit BMP is supported."
            );
        }

        $imageData = substr($blob, $r['image_data_offset']);
        $r['hex']  = $this->parseBitmapArray($imageData, $r['width'], $r['height']);

        return $r;
    }

    /**
     * Parse raw pixel data into a 2D array of RGB hex strings.
     *
     * BMP stores rows bottom-to-top and each row may be padded to a 4-byte boundary.
     *
     * @param  string $data   Raw pixel data (starting from image_data_offset)
     * @param  int    $width  Image width in pixels
     * @param  int    $height Image height in pixels
     * @return array<int, array<int, string>> [row][col] => 'rrggbb' hex string
     */
    public function parseBitmapArray(string $data, int $width, int $height): array
    {
        // 24-bit BMP: 3 bytes per pixel, rows padded to 4-byte boundary
        $rowStride = (int) (ceil($width * 3 / 4) * 4);

        $rows = [];
        for ($i = 0; $i < $height; $i++) {
            $rowOffset = $i * $rowStride;
            $row       = [];
            for ($j = 0; $j < $width; $j++) {
                $pos   = $rowOffset + $j * 3;
                // BMP stores pixels as BGR, not RGB
                $b     = bin2hex($data[$pos]     ?? "\x00");
                $g     = bin2hex($data[$pos + 1] ?? "\x00");
                $r     = bin2hex($data[$pos + 2] ?? "\x00");
                $row[] = $r . $g . $b; // output as RGB for consistency with original codebase
            }
            $rows[] = $row;
        }

        // BMP rows are stored bottom-to-top; reverse to get top-to-bottom order
        return array_reverse($rows);
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    /** Read an unsigned 32-bit little-endian integer from $data at $offset */
    private function readU32(string $data, int $offset): int
    {
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('V', substr($data, $offset, 4));
        return $unpacked[1];
    }

    /** Read an unsigned 16-bit little-endian integer from $data at $offset */
    private function readU16(string $data, int $offset): int
    {
        /** @var array{1: int} $unpacked */
        $unpacked = unpack('v', substr($data, $offset, 2));
        return $unpacked[1];
    }
}
