<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Enums;

/**
 * Supported font families bundled with the package.
 *
 * The enum value is the actual filename stored in the fonts/ directory.
 */
enum FontFamily: string
{
    /** 楷書（預設）- 適合中文內容 */
    case KAIU = 'KAIU.TTF';

    /** 思源黑體繁中 - 適合中文內容，字型較清晰 */
    case NOTO_SANS_CJK_TC = 'NotoSansCJKtc-Regular.otf';

    /** Arial Narrow Bold */
    case ARIAL_NARROW_BOLD = 'ARIALNB.TTF';

    /** Arial Bold (Alternative) */
    case ARIAL_BOLD_ALT = 'Arial Bold.ttf';

    /** Arial Regular */
    case ARIAL = 'arial.ttf';

    /** Arial Bold */
    case ARIAL_BOLD = 'arialbd.ttf';

    /**
     * Human-readable label for this font.
     */
    public function label(): string
    {
        return match ($this) {
            self::KAIU             => '楷書 (KAIU)',
            self::NOTO_SANS_CJK_TC => '思源黑體繁中 (Noto Sans CJK TC)',
            self::ARIAL_NARROW_BOLD => 'Arial Narrow Bold',
            self::ARIAL_BOLD_ALT   => 'Arial Bold (Alt)',
            self::ARIAL            => 'Arial Regular',
            self::ARIAL_BOLD       => 'Arial Bold',
        };
    }

    /**
     * Whether this font is primarily designed for CJK (Chinese, Japanese, Korean) characters.
     */
    public function isCjk(): bool
    {
        return match ($this) {
            self::KAIU, self::NOTO_SANS_CJK_TC => true,
            default => false,
        };
    }
}
