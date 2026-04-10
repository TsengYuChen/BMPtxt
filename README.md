# tsengyuchen/bmptxt

> Convert text and images to BMP bitmap data for **thermal label printers** using the EZPL protocol.  
> Compatible with **Laravel 10+**, **Yii2+**, **CodeIgniter 4+**, or any PSR-4 PHP project.

[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

---

## Requirements

- PHP **8.2+**
- Extension: `ext-gd` (with FreeType support for TTF rendering)
- Extension: `ext-mbstring`
- Extension: `ext-sockets` (only if sending directly to printer)

---

## Installation

```bash
composer require tsengyuchen/bmptxt
```

---

## Quick Start

### Text → EZPL

```php
use TsengYuChen\BMPtxt\BmpConverter;
use TsengYuChen\BMPtxt\Enums\FontFamily;

$converter = new BmpConverter();

$result = $converter->text('薄型 SMCG TVS 無鹵')
    ->font(FontFamily::KAIU)   // 楷書字型 (default)
    ->size(12)                 // point size
    ->dpi(232)                 // printer DPI
    ->toEzpl(x: 0, y: 0);

// Hex string for HTTP response (caller unpacks it)
echo $result->toHex();

// Binary string ready for socket_send()
$binary = $result->toBinary();
```

### Image → Black/White EZPL

```php
$result = $converter->image()
    ->fromUrl('http://192.168.1.10/logo.png')
    ->scale(1.5)
    ->rotate(10)
    ->blackWhite(threshold: 0.8)
    ->toEzpl(x: 100, y: 120);
```

### Sending to a Label Printer (EZPL via TCP Socket)

```php
$binary = $result->toBinary();

$host = '192.168.8.243';
$port = 9100;

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $host, $port);

$ln  = "\r\n";
$cmd = "^C1{$ln}^P1{$ln}^L{$ln}^W90{$ln}^Q100,3{$ln}" . $binary . "E{$ln}";

socket_send($socket, $cmd, strlen($cmd), 0);
socket_close($socket);
```

---

## Available Fonts

| `FontFamily` case | File | Best for |
|---|---|---|
| `KAIU` *(default)* | `KAIU.TTF` | Chinese (楷書) |
| `NOTO_SANS_CJK_TC` | `NotoSansCJKtc-Regular.otf` | Chinese (清晰) |
| `ARIAL_NARROW_BOLD` | `ARIALNB.TTF` | English |
| `ARIAL` | `arial.ttf` | English |
| `ARIAL_BOLD` | `arialbd.ttf` | English Bold |

Custom font directory:
```php
$converter = BmpConverter::create('/path/to/my/fonts');
```

---

## Output Formats

| Method | Returns | Use for |
|---|---|---|
| `->toHex()` | `string` (hex) | HTTP response → `pack("H*", $hex)` on client |
| `->toBinary()` | `string` (binary) | Direct `socket_send()` |
| `->toRaw()` | `string` | `"widthBytes,height,{decimal_data}"` |

---

## Framework Integration

各框架有獨立的詳細整合說明文件：

| 框架 | 文件 | 整合方式 |
|------|------|---------|
| **Laravel 10 / 11** | [docs/laravel.md](docs/laravel.md) | Auto-Discovery + Facade + Middleware |
| **Yii2** | [docs/yii2.md](docs/yii2.md) | Component + assertInternalRequest() |
| **CodeIgniter 4** | [docs/codeigniter4.md](docs/codeigniter4.md) | Service + Filter |

### Laravel 快速範例

```php
// 自動 Auto-Discovery，無需手動註冊
use TsengYuChen\BMPtxt\Adapters\Laravel\Facades\Bmptxt;

// 文字轉 EZPL
$hex = Bmptxt::text('薄型 SMCG TVS 無鹵')
    ->size(12)
    ->toEzpl(x: 0, y: 0)
    ->toHex();

// 發布設定
// php artisan vendor:publish --tag=bmptxt-config
```

### Yii2 快速範例

```php
// config/main.php
'components' => [
    'bmptxt' => ['class' => \TsengYuChen\BMPtxt\Adapters\Yii2\BmptxtComponent::class],
],

// Controller
Yii::$app->bmptxt->assertInternalRequest();
$hex = Yii::$app->bmptxt->text('測試')->size(12)->toEzpl()->toHex();
```

### CodeIgniter 4 快速範例

```php
// app/Config/Services.php 中新增 bmptxt() 方法（詳見文件）

// Controller
$hex = service('bmptxt')->text('測試')->size(12)->toEzpl()->toHex();
```

---

## Running Tests

```bash
composer install
./vendor/bin/phpunit
```

---

## Architecture

```
src/
├── BmpConverter.php            # Main entry point
├── Contracts/
│   └── EzplOutputInterface.php
├── Core/
│   ├── Converter/
│   │   └── GdToEzplConverter.php   # GD image → EZPL bytes (core algorithm)
│   ├── Font/
│   │   └── FontManager.php
│   ├── Parser/
│   │   ├── BmpParser.php           # BMP binary format parser
│   │   └── WbmpParser.php          # WBMP binary format parser
│   └── Renderer/
│       ├── TextRenderer.php        # TTF text → GD → EZPL
│       └── ImageRenderer.php       # URL/file image → GD → EZPL
├── Enums/
│   └── FontFamily.php
├── Exceptions/
│   ├── BmptxtException.php
│   ├── BmpParserException.php
│   ├── FontNotFoundException.php
│   ├── ImageFetchException.php
│   └── RenderException.php
└── ValueObjects/
    └── EzplResult.php
```

---

## License

MIT © Tseng Yu Chen
