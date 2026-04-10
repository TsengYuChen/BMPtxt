# CodeIgniter 4 整合指南

## 安裝

```bash
composer require tsengyuchen/bmptxt
```

---

## 步驟一：設定 Config

在 `app/Config/Bmptxt.php` 建立設定檔（繼承套件預設值）：

```php
<?php

namespace Config;

use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Config\Bmptxt as BaseBmptxt;

class Bmptxt extends BaseBmptxt
{
    /**
     * 自訂字型目錄，null = 使用套件內建字型
     */
    public ?string $fontsPath = null;

    /**
     * 印表機 DPI（GODEX 標準為 232）
     */
    public int $defaultDpi = 232;

    /**
     * 內部 IP 白名單（供 InternalIpFilter 使用）
     *
     * @var string[]
     */
    public array $allowedIps = [
        '127.0.0.1',
        '::1',
        '192.168.0.0/16',
        '10.0.0.0/8',
    ];
}
```

---

## 步驟二：註冊 Service

在 `app/Config/Services.php` 新增 `bmptxt()` 方法：

```php
<?php

namespace Config;

use CodeIgniter\Config\BaseService;
use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Services\BmptxtService;
use TsengYuChen\BMPtxt\BmpConverter;

class Services extends BaseService
{
    // ... 既有的 services ...

    /**
     * BMPtxt 標籤印表機 bitmap 轉換服務
     */
    public static function bmptxt(bool $getShared = true): BmpConverter
    {
        if ($getShared) {
            return static::getSharedInstance('bmptxt');
        }

        return BmptxtService::make();
    }
}
```

---

## 步驟三：註冊 IP Filter（可選）

在 `app/Config/Filters.php` 新增 alias：

```php
<?php

namespace Config;

use CodeIgniter\Config\Filters as BaseFilters;
use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Filters\InternalIpFilter;

class Filters extends BaseFilters
{
    public array $aliases = [
        // ... 既有的 aliases ...
        'internal.ip' => InternalIpFilter::class,
    ];
}
```

---

## 使用方式

### Controller 中使用 service()

```php
<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use TsengYuChen\BMPtxt\Enums\FontFamily;

class LabelController extends Controller
{
    /**
     * POST /label/text
     * 文字轉 EZPL bitmap（hex 編碼回傳）
     */
    public function text(): ResponseInterface
    {
        $text = $this->request->getPost('text') ?? '';
        $size = (float) ($this->request->getPost('size') ?? 12);
        $x    = (int)   ($this->request->getPost('x') ?? 0);
        $y    = (int)   ($this->request->getPost('y') ?? 0);

        $hex = service('bmptxt')
            ->text($text)
            ->font(FontFamily::KAIU)
            ->size($size)
            ->toEzpl(x: $x, y: $y)
            ->toHex();

        return $this->response
            ->setContentType('text/plain')
            ->setBody($hex);
    }

    /**
     * POST /label/image
     * 圖片轉黑白 EZPL bitmap（hex 編碼回傳）
     */
    public function image(): ResponseInterface
    {
        $imgUrl = $this->request->getPost('img_url') ?? '';
        $scale  = (float) ($this->request->getPost('scale') ?? 1.0);
        $rotate = (float) ($this->request->getPost('rotate') ?? 0.0);
        $bw     = (float) ($this->request->getPost('bw') ?? 0.8);
        $x      = (int)   ($this->request->getPost('x') ?? 0);
        $y      = (int)   ($this->request->getPost('y') ?? 0);

        $hex = service('bmptxt')
            ->image()
            ->fromUrl($imgUrl)
            ->scale($scale)
            ->rotate($rotate)
            ->blackWhite($bw)
            ->toEzpl(x: $x, y: $y)
            ->toHex();

        return $this->response
            ->setContentType('text/plain')
            ->setBody($hex);
    }
}
```

---

## IP 白名單保護

### 方式一：套用 Filter 到路由群組（推薦）

在 `app/Config/Routes.php`：

```php
$routes->group('label', ['filter' => 'internal.ip'], static function ($routes) {
    $routes->post('text',  'LabelController::text');
    $routes->post('image', 'LabelController::image');
});
```

### 方式二：在 Controller 手動檢查

```php
use TsengYuChen\BMPtxt\Support\IpChecker;

class LabelController extends Controller
{
    public function initController(/* ... */)
    {
        parent::initController(/* ... */);

        $config    = config('Bmptxt');
        $clientIp  = $this->request->getIPAddress();

        if (!IpChecker::isAllowed($clientIp, $config->allowedIps)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException(
                'Forbidden: internal access only.'
            );
        }
    }
}
```

---

## 直接傳送到印表機（TCP Socket）

```php
$binary = service('bmptxt')
    ->text('出貨品號：A12345')
    ->size(14)
    ->toEzpl(x: 0, y: 0)
    ->toBinary();

$host = '192.168.8.243';
$port = 9100;
$ln   = "\r\n";

$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_connect($socket, $host, $port);

$cmd = "^C1{$ln}^P1{$ln}^L{$ln}^W90{$ln}^Q100,3{$ln}" . $binary . "E{$ln}";
socket_send($socket, $cmd, strlen($cmd), 0);
socket_close($socket);
```

---

## 可用字型

| `FontFamily` case | 適用 |
|---|---|
| `FontFamily::KAIU` | 楷書（中文，預設）|
| `FontFamily::NOTO_SANS_CJK_TC` | 思源黑體繁中 |
| `FontFamily::ARIAL` | Arial Regular |
| `FontFamily::ARIAL_BOLD` | Arial Bold |
| `FontFamily::ARIAL_NARROW_BOLD` | Arial Narrow Bold |

自訂字型目錄：

```php
// app/Config/Bmptxt.php
public ?string $fontsPath = APPPATH . 'ThirdParty/bmptxt/fonts';
```
