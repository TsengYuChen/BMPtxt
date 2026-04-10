<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Contracts;

use TsengYuChen\BMPtxt\ValueObjects\EzplResult;

/**
 * Implemented by renderers that can produce EZPL output.
 */
interface EzplOutputInterface
{
    /**
     * Render content and return an EZPL result object.
     *
     * @param int $x X position on label (in dots)
     * @param int $y Y position on label (in dots)
     */
    public function toEzpl(int $x = 0, int $y = 0): EzplResult;
}
