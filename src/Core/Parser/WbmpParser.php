<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Parser;

use TsengYuChen\BMPtxt\Exceptions\BmpParserException;

/**
 * Parses WBMP (Wireless Bitmap) binary data.
 *
 * WBMP is a simple 1-bit monochrome image format used in WAP applications.
 * Kept for completeness as the original codebase supported it.
 */
class WbmpParser
{
    /**
     * Parse a WBMP binary string into structured data.
     *
     * @param  string $blob Raw WBMP binary data
     * @return array{
     *     type: int,
     *     fixed_header: int,
     *     width: int,
     *     height: int,
     *     hex: array<int, string>,
     *     bin: array<int, string>
     * }
     * @throws BmpParserException on empty or invalid data
     */
    public function parse(string $blob): array
    {
        if ($blob === '') {
            throw new BmpParserException('WBMP data is empty.');
        }

        $offset = 0;

        $type        = ord($blob[$offset++]);
        $fixedHeader = ord($blob[$offset++]);

        [$width,  $offset] = $this->readMultibyte($blob, $offset);
        [$height, $offset] = $this->readMultibyte($blob, $offset);

        $pixelData = substr($blob, $offset);

        $result = [
            'type'         => $type,
            'fixed_header' => $fixedHeader,
            'width'        => $width,
            'height'       => $height,
            'hex'          => [],
            'bin'          => [],
        ];

        if ($pixelData !== '' && $height > 0) {
            ['hex' => $result['hex'], 'bin' => $result['bin']]
                = $this->parseBitmapFormat($pixelData, $height);
        }

        return $result;
    }

    /**
     * Parse raw pixel bytes into per-row hex and binary strings.
     *
     * WBMP uses 0=black, 1=white (inverted from BMP convention).
     * We invert each byte (255 - byte) to normalize to 0=white, 1=black.
     *
     * @return array{hex: array<int, string>, bin: array<int, string>}
     */
    public function parseBitmapFormat(string $data, int $height): array
    {
        $totalBytes = strlen($data);
        $bytesPerRow = (int) ($totalBytes / $height); // integer division

        $hexRows = [];
        $binRows = [];

        for ($row = 0; $row < $height; $row++) {
            $rowOffset = $row * $bytesPerRow;
            $rowHex    = '';
            $rowBin    = '';

            // Last byte of each row may be padding; process only full pixel bytes
            for ($b = 0; $b < $bytesPerRow - 1; $b++) {
                $byte    = 255 - ord($data[$rowOffset + $b]); // invert: 0→white, 1→black
                $rowHex .= str_pad(dechex($byte), 2, '0', STR_PAD_LEFT);
                $rowBin .= str_pad(decbin($byte), 8, '0', STR_PAD_LEFT);
            }

            $hexRows[] = $rowHex;
            $binRows[] = $rowBin;
        }

        return ['hex' => $hexRows, 'bin' => $binRows];
    }

    /**
     * Read a WBMP multibyte integer (variable-length encoding).
     * If the high bit is set, the value spans two bytes.
     *
     * @param  string $data
     * @param  int    $offset Current read position
     * @return array{0: int, 1: int}  [decoded_value, next_offset]
     */
    private function readMultibyte(string $data, int $offset): array
    {
        $byte = ord($data[$offset]);

        if ($byte > 127) {
            // High bit set: value = (first_byte - 128) * 128 + second_byte
            $high = ($byte - 128) * 128;
            $low  = ord($data[$offset + 1]);
            return [$high + $low, $offset + 2];
        }

        return [$byte, $offset + 1];
    }
}
