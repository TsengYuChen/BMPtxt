<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Tests\Unit\Parser;

use TsengYuChen\BMPtxt\Core\Parser\BmpParser;
use TsengYuChen\BMPtxt\Exceptions\BmpParserException;
use TsengYuChen\BMPtxt\Tests\TestCase;

class BmpParserTest extends TestCase
{
    private BmpParser $parser;

    protected function setUp(): void
    {
        $this->parser = new BmpParser();
    }

    public function test_throws_on_empty_data(): void
    {
        $this->expectException(BmpParserException::class);
        $this->expectExceptionMessageMatches('/minimum required is \d+ bytes/');
        $this->parser->parse('');
    }

    public function test_throws_on_invalid_signature(): void
    {
        $this->expectException(BmpParserException::class);
        $this->expectExceptionMessageMatches("/signature/");
        $this->parser->parse(str_repeat("\x00", 54));
    }

    public function test_throws_on_data_shorter_than_header(): void
    {
        $this->expectException(BmpParserException::class);
        $this->parser->parse('BM' . str_repeat("\x00", 10));
    }

    public function test_parse_1x1_white_bmp(): void
    {
        // Minimal valid 24-bit BMP: 1×1 white pixel
        // Header (54 bytes) + 1 pixel (3 bytes) + 1 byte padding = 58 bytes
        $bmp = $this->make1x1WhiteBmp();

        $result = $this->parser->parse($bmp);

        $this->assertSame('BM', $result['type']);
        $this->assertSame(1, $result['width']);
        $this->assertSame(1, $result['height']);
        $this->assertSame(24, $result['bit_pixel']);
        $this->assertCount(1, $result['hex']);          // 1 row
        $this->assertCount(1, $result['hex'][0]);       // 1 column
        $this->assertSame('ffffff', $result['hex'][0][0]); // white pixel
    }

    public function test_bitmap_array_reverses_rows(): void
    {
        // BMP stores rows bottom-up; parseBitmapArray() should reverse them
        // Create a 2×1 BMP: top pixel = black, bottom pixel = white (stored as white-then-black)
        $bmp = $this->make2x1Bmp(topBlack: true);

        $result = $this->parser->parse($bmp);

        $this->assertCount(2, $result['hex']);         // 2 rows
        $this->assertSame('000000', $result['hex'][0][0]); // row 0 = black (top)
        $this->assertSame('ffffff', $result['hex'][1][0]); // row 1 = white (bottom)
    }

    // ─── BMP construction helpers ─────────────────────────────────────────────

    /**
     * Build a minimal valid 24-bit BMP with a single white 1×1 pixel.
     */
    private function make1x1WhiteBmp(): string
    {
        $width      = 1;
        $height     = 1;
        $rowStride  = 4; // padded to 4-byte boundary: ceil(1*3/4)*4 = 4
        $imageSize  = $rowStride * $height;
        $fileSize   = 54 + $imageSize;
        $dataOffset = 54;

        $header = pack('A2', 'BM')          // signature
            . pack('V', $fileSize)          // file size
            . pack('v', 0) . pack('v', 0)  // reserved
            . pack('V', $dataOffset)        // image data offset
            . pack('V', 40)                 // DIB header size (BITMAPINFOHEADER)
            . pack('V', $width)             // width
            . pack('V', $height)            // height
            . pack('v', 1)                  // planes
            . pack('v', 24)                 // bits per pixel
            . pack('V', 0)                  // compression (BI_RGB)
            . pack('V', $imageSize)         // image size
            . pack('V', 2835)               // h_resolution (72 dpi)
            . pack('V', 2835)               // v_resolution
            . pack('V', 0)                  // used colors
            . pack('V', 0);                 // important colors

        // Pixel data: BGR order; 1 white pixel + 1 byte padding
        $pixels = "\xFF\xFF\xFF\x00"; // blue=255, green=255, red=255, pad=0

        return $header . $pixels;
    }

    /**
     * Build a minimal 24-bit BMP with 2 rows × 1 column.
     * BMP stores rows bottom-to-top, so we store white first then black.
     */
    private function make2x1Bmp(bool $topBlack): string
    {
        $width      = 1;
        $height     = 2;
        $rowStride  = 4;
        $imageSize  = $rowStride * $height;
        $fileSize   = 54 + $imageSize;

        $header = pack('A2', 'BM')
            . pack('V', $fileSize)
            . pack('v', 0) . pack('v', 0)
            . pack('V', 54)
            . pack('V', 40)
            . pack('V', $width)
            . pack('V', $height)
            . pack('v', 1)
            . pack('v', 24)
            . pack('V', 0)
            . pack('V', $imageSize)
            . pack('V', 2835)
            . pack('V', 2835)
            . pack('V', 0)
            . pack('V', 0);

        if ($topBlack) {
            // BMP stores bottom row first: white (bottom) then black (top)
            $pixels = "\xFF\xFF\xFF\x00" // row 0 (bottom) = white
                    . "\x00\x00\x00\x00"; // row 1 (top)    = black
        } else {
            $pixels = "\x00\x00\x00\x00" // row 0 (bottom) = black
                    . "\xFF\xFF\xFF\x00"; // row 1 (top)    = white
        }

        return $header . $pixels;
    }
}
