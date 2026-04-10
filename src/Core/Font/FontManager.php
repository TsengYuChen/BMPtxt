<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Core\Font;

use TsengYuChen\BMPtxt\Enums\FontFamily;
use TsengYuChen\BMPtxt\Exceptions\FontNotFoundException;

/**
 * Manages font file paths for the BMPtxt package.
 *
 * By default, looks in the package's bundled fonts/ directory.
 * Can be configured to use a custom fonts directory.
 */
class FontManager
{
    private string $fontsPath;

    public function __construct(?string $fontsPath = null)
    {
        $this->fontsPath = $fontsPath ?? $this->defaultFontsPath();
    }

    /**
     * Get the absolute file path for a given font family.
     *
     * @throws FontNotFoundException if the font file does not exist
     */
    public function getPath(FontFamily $font): string
    {
        $path = $this->fontsPath . DIRECTORY_SEPARATOR . $font->value;

        if (!file_exists($path)) {
            throw new FontNotFoundException(
                "Font file not found: [{$path}] (Font: {$font->label()}). "
                . "Ensure the font exists in the fonts directory or configure a custom fonts path."
            );
        }

        return $path;
    }

    /**
     * Set a custom directory containing font files.
     * Returns a new instance (immutable style).
     */
    public function withFontsPath(string $path): static
    {
        $clone = clone $this;
        $clone->fontsPath = rtrim($path, DIRECTORY_SEPARATOR . '/\\');
        return $clone;
    }

    public function getFontsPath(): string
    {
        return $this->fontsPath;
    }

    /**
     * Returns all FontFamily cases whose font file exists in the configured path.
     *
     * @return FontFamily[]
     */
    public function getAvailableFonts(): array
    {
        return array_values(array_filter(
            FontFamily::cases(),
            fn(FontFamily $font): bool => file_exists(
                $this->fontsPath . DIRECTORY_SEPARATOR . $font->value
            )
        ));
    }

    /**
     * The package's bundled fonts directory (two levels up from this file).
     * Resolves to: <package_root>/fonts/
     */
    private function defaultFontsPath(): string
    {
        return dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'fonts';
    }
}
