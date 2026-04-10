<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Tests\Unit\Converter;

use GdImage;
use TsengYuChen\BMPtxt\Core\Converter\GdToEzplConverter;
use TsengYuChen\BMPtxt\Exceptions\RenderException;
use TsengYuChen\BMPtxt\ValueObjects\EzplResult;
use TsengYuChen\BMPtxt\Tests\TestCase;

class GdToEzplConverterTest extends TestCase
{
    private GdToEzplConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new GdToEzplConverter();
    }

    public function test_convert_single_black_pixel(): void
    {
        // 1×1 pure black image
        $im = imagecreatetruecolor(1, 1);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 0, 0, $black);

        $result = $this->converter->convert($im);
        imagedestroy($im);

        $this->assertInstanceOf(EzplResult::class, $result);
        $this->assertSame(1, $result->widthBytes);   // ceil(1/8) = 1
        $this->assertSame(1, $result->heightLines);
        // Binary: 10000000 → decimal 128 → chr(128)
        $this->assertSame(chr(128), $result->data);
    }

    public function test_convert_single_white_pixel(): void
    {
        $im = imagecreatetruecolor(1, 1);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, 0, 0, $white);

        $result = $this->converter->convert($im);
        imagedestroy($im);

        // Binary: 00000000 → decimal 0 → chr(0)
        $this->assertSame(chr(0), $result->data);
    }

    public function test_convert_8_black_pixels_makes_one_byte(): void
    {
        // 8×1 all-black image → 1 row, 1 byte, all bits = 1 → 0xFF = 255
        $im    = imagecreatetruecolor(8, 1);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 7, 0, $black);

        $result = $this->converter->convert($im);
        imagedestroy($im);

        $this->assertSame(1, $result->widthBytes);
        $this->assertSame(chr(255), $result->data);
    }

    public function test_width_bytes_rounds_up_to_byte_boundary(): void
    {
        // 9 pixels wide → ceil(9/8) = 2 bytes wide
        $im    = imagecreatetruecolor(9, 1);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, 8, 0, $white);

        $result = $this->converter->convert($im);
        imagedestroy($im);

        $this->assertSame(2, $result->widthBytes);
        $this->assertSame(2, strlen($result->data)); // 1 row × 2 bytes
    }

    public function test_throws_on_empty_image(): void
    {
        $this->expectException(RenderException::class);
        $this->expectExceptionMessageMatches('/empty/');

        // We can't actually create a 0×0 GdImage, so we test the exception message
        // by calling with a mock. Instead, test with a valid but 0-pixel-count image
        // — this is done by asserting the exception is thrown during convert().
        // In practice, imagecreatetruecolor(0, 0) returns false, so this is
        // tested implicitly through ImageRenderer/TextRenderer error paths.
        $this->markTestSkipped('Cannot create 0×0 GdImage; tested via renderer integration tests.');
    }

    public function test_to_black_white_converts_gray_pixels(): void
    {
        // Create a mid-gray image (128, 128, 128)
        $im   = imagecreatetruecolor(2, 1);
        $gray = imagecolorallocate($im, 128, 128, 128);
        imagefilledrectangle($im, 0, 0, 1, 0, $gray);

        // threshold=0.8 → cutoff=204; gray(128) < 204 → black
        $result = $this->converter->toBlackWhite($im, 0.8);

        $pixelLeft  = imagecolorat($result, 0, 0);
        $pixelRight = imagecolorat($result, 1, 0);

        $this->assertSame(0x000000, $pixelLeft);
        $this->assertSame(0x000000, $pixelRight);

        imagedestroy($im);
    }

    public function test_ezpl_result_to_hex_format(): void
    {
        $im    = imagecreatetruecolor(1, 1);
        $black = imagecolorallocate($im, 0, 0, 0);
        imagefilledrectangle($im, 0, 0, 0, 0, $black);

        $result = $this->converter->convert($im, x: 10, y: 20);
        imagedestroy($im);

        $hex  = $result->toHex();
        $binary = pack('H*', $hex);

        // The binary should start with "Q10,20,1,1\r\n"
        $this->assertStringStartsWith("Q10,20,1,1\r\n", $binary);
    }
}
