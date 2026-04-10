# Laravel 10 / 11 整合指南

## 安裝

```bash
composer require tsengyuchen/bmptxt
```

Laravel 會透過 **Package Auto-Discovery** 自動載入 Service Provider 和 Facade，不需要手動在 `config/app.php` 新增任何設定。

---

## 設定

### 發布設定檔

```bash
php artisan vendor:publish --tag=bmptxt-config
```

這會在 `config/bmptxt.php` 建立設定檔：

```php
return [
    // 印表機 DPI（GODEX 標準為 232）
    'default_dpi' => 232,

    // 預設字型 ('kaiu', 'noto_sans_cjk_tc', 'arial', 'arial_bold' ...)
    'default_font' => 'kaiu',

    // 自訂字型目錄，null = 使用套件內建字型
    'fonts_path' => null,

    // 內部 IP 白名單（供 InternalIpMiddleware 使用）
    'allowed_ips' => [
        '127.0.0.1',
        '::1',
        '192.168.0.0/16',
        '10.0.0.0/8',
    ],
];
```

### 發布字型（可選）

```bash
php artisan vendor:publish --tag=bmptxt-fonts
```

字型會複製到 `resources/fonts/bmptxt/`，方便自行替換。
接著在 `config/bmptxt.php` 更新：

```php
'fonts_path' => resource_path('fonts/bmptxt'),
```

---

## 使用方式

### 方式一：Facade（推薦）

```php
use TsengYuChen\BMPtxt\Adapters\Laravel\Facades\Bmptxt;
use TsengYuChen\BMPtxt\Enums\FontFamily;

// 文字 → EZPL hex（用於 HTTP 回應）
$hex = Bmptxt::text('薄型 SMCG TVS 無鹵')
    ->font(FontFamily::KAIU)
    ->size(12)
    ->dpi(232)
    ->toEzpl(x: 0, y: 0)
    ->toHex();

return response($hex)->header('Content-Type', 'text/plain');
```

```php
// 圖片 → 黑白 EZPL
$hex = Bmptxt::image()
    ->fromUrl('http://192.168.1.10/logo.png')
    ->scale(1.5)
    ->rotate(10)
    ->blackWhite(threshold: 0.8)
    ->toEzpl(x: 100, y: 120)
    ->toHex();
```

### 方式二：依賴注入（DI）

```php
use TsengYuChen\BMPtxt\BmpConverter;

class LabelController extends Controller
{
    public function __construct(
        private readonly BmpConverter $bmptxt,
    ) {}

    public function printText(Request $request): Response
    {
        $hex = $this->bmptxt
            ->text($request->validated('text'))
            ->size((float) $request->validated('size', 12))
            ->toEzpl(
                x: (int) $request->validated('x', 0),
                y: (int) $request->validated('y', 0),
            )
            ->toHex();

        return response($hex)->header('Content-Type', 'text/plain');
    }
}
```

---

## IP 白名單保護（InternalIpMiddleware）

對應原始代碼的 `is_external()` 限制，限定只有內部 IP 才能呼叫印表機 API。

### Laravel 11（bootstrap/app.php）

```php
use TsengYuChen\BMPtxt\Adapters\Laravel\Http\Middleware\InternalIpMiddleware;

->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'internal.ip' => InternalIpMiddleware::class,
    ]);
})
```

### Laravel 10（app/Http/Kernel.php）

```php
use TsengYuChen\BMPtxt\Adapters\Laravel\Http\Middleware\InternalIpMiddleware;

protected $middlewareAliases = [
    // ...
    'internal.ip' => InternalIpMiddleware::class,
];
```

### 套用到路由

```php
// routes/api.php
use TsengYuChen\BMPtxt\Adapters\Laravel\Facades\Bmptxt;

Route::middleware('internal.ip')->group(function () {
    Route::post('/label/text', function (Request $request) {
        $hex = Bmptxt::text($request->text)
            ->size($request->size ?? 12)
            ->toEzpl()
            ->toHex();

        return response($hex);
    });

    Route::post('/label/image', function (Request $request) {
        $hex = Bmptxt::image()
            ->fromUrl($request->img_url)
            ->blackWhite($request->bw ?? 0.8)
            ->toEzpl(x: $request->x ?? 0, y: $request->y ?? 0)
            ->toHex();

        return response($hex);
    });
});
```

---

## 直接傳送到印表機（TCP Socket）

```php
use TsengYuChen\BMPtxt\Adapters\Laravel\Facades\Bmptxt;

$binary = Bmptxt::text('出貨品號：A12345')
    ->size(14)
    ->toEzpl(x: 0, y: 0)
    ->toBinary();

$host   = '192.168.8.243';
$port   = 9100;
$ln     = "\r\n";

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $host, $port);

$cmd = "^C1{$ln}^P1{$ln}^L{$ln}^W90{$ln}^Q100,3{$ln}" . $binary . "E{$ln}";
socket_send($socket, $cmd, strlen($cmd), 0);
socket_close($socket);
```

---

## 常見問題

### Facade 沒有自動補全？

在 `composer.json` 中加入：

```json
"require-dev": {
    "barryvdh/laravel-ide-helper": "^3.0"
}
```

然後執行：

```bash
php artisan ide-helper:generate
```

### 更換字型？

```php
use TsengYuChen\BMPtxt\Enums\FontFamily;

Bmptxt::text('Noto Sans 測試')
    ->font(FontFamily::NOTO_SANS_CJK_TC)
    ->size(12)
    ->toEzpl()
    ->toHex();
```

可用字型請參考 [FontFamily Enum](../src/Enums/FontFamily.php)。
