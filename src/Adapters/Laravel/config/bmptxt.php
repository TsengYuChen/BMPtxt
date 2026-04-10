<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Printer Output DPI
    |--------------------------------------------------------------------------
    | The DPI of the target label printer.
    | GODEX label printers typically use 203 or 232 DPI.
    |
    */
    'default_dpi' => 232,

    /*
    |--------------------------------------------------------------------------
    | Default Font Family
    |--------------------------------------------------------------------------
    | Default font to use when none is specified.
    | Must be one of the FontFamily enum case names (lowercase).
    | e.g. 'kaiu', 'noto_sans_cjk_tc', 'arial', 'arial_bold'
    |
    */
    'default_font' => 'kaiu',

    /*
    |--------------------------------------------------------------------------
    | Custom Fonts Path
    |--------------------------------------------------------------------------
    | Absolute path to a directory containing font files.
    | Set to null to use the bundled fonts in the package's fonts/ directory.
    |
    | After running: php artisan vendor:publish --tag=bmptxt-fonts
    | You can set this to: resource_path('fonts/bmptxt')
    |
    */
    'fonts_path' => null,

    /*
    |--------------------------------------------------------------------------
    | Cache Settings (PSR-16)
    |--------------------------------------------------------------------------
    | Set a TTL (in seconds) to cache output results.
    | This avoids re-rendering the same text/image multiple times.
    | Set to null to cache indefinitely, or 0/false to disable entirely.
    |
    */
    'cache_ttl' => 3600,

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs (for InternalIpMiddleware)
    |--------------------------------------------------------------------------
    | List of IP addresses or CIDR ranges allowed to call label-printing routes.
    | Corresponds to the original is_external() check in the legacy codebase.
    |
    */
    'allowed_ips' => [
        '127.0.0.1',
        '::1',
        '192.168.0.0/16',
        '10.0.0.0/8',
    ],
];
