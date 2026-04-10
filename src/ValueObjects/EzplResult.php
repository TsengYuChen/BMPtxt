<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\ValueObjects;

/**
 * Immutable result object containing EZPL-formatted bitmap data.
 *
 * EZPL (EZ-Print Language) is the label printer command language
 * used by GODEX and similar thermal label printers.
 */
readonly class EzplResult
{
    /**
     * @param string $data        Raw binary bitmap data (packed bytes, 1 bit per pixel)
     * @param int    $widthBytes  Width in bytes: ceil(pixel_width / 8)
     * @param int    $heightLines Height in pixel lines
     * @param int    $x           X position on label (in dots)
     * @param int    $y           Y position on label (in dots)
     */
    public function __construct(
        public readonly string $data,
        public readonly int    $widthBytes,
        public readonly int    $heightLines,
        public readonly int    $x = 0,
        public readonly int    $y = 0,
    ) {}

    /**
     * Output as hex-encoded EZPL "Q" command string.
     *
     * Format: Q{x},{y},{widthBytes},{heightLines}\r\n{data}\r\n
     * The whole string is then hex-encoded for safe HTTP transport.
     *
     * Usage: Caller does pack("H*", $result->toHex()) before sending to printer socket.
     */
    public function toHex(): string
    {
        $cmd = "Q{$this->x},{$this->y},{$this->widthBytes},{$this->heightLines}\r\n{$this->data}\r\n";
        return bin2hex($cmd);
    }

    /**
     * Output as raw binary string, ready to write directly to a printer socket.
     *
     * Usage: socket_send($socket, $result->toBinary(), ...)
     */
    public function toBinary(): string
    {
        return pack('H*', $this->toHex());
    }

    /**
     * Output in RAW format: "{widthBytes},{heightLines},{data}"
     *
     * Used by getBmpTextRAW (decimal byte values, comma-separated).
     * Each byte in $data is represented as a 3-digit decimal string.
     */
    public function toRaw(): string
    {
        $bytes = str_split($this->data);
        $decimals = array_map(fn(string $b): string => sprintf('%03d', ord($b)), $bytes);
        return "{$this->widthBytes},{$this->heightLines}," . implode('', $decimals);
    }

    /**
     * Return width in pixels (approximate, may be rounded up to 8-bit boundary).
     */
    public function widthPixels(): int
    {
        return $this->widthBytes * 8;
    }
}
