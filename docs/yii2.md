# Yii2 整合指南

## 安裝

```bash
composer require tsengyuchen/bmptxt
```

---

## 設定

在 `config/main.php`（或 `config/web.php`）的 `components` 中新增：

```php
use TsengYuChen\BMPtxt\Adapters\Yii2\BmptxtComponent;

return [
    'components' => [
        'bmptxt' => [
            'class'      => BmptxtComponent::class,

            // 自訂字型目錄，null = 使用套件內建字型
            'fontsPath'  => null,

            // 印表機 DPI
            'defaultDpi' => 232,

            // 內部 IP 白名單（供 assertInternalRequest() 使用）
            'allowedIps' => [
                '127.0.0.1',
                '::1',
                '192.168.0.0/16',
                '10.0.0.0/8',
            ],
        ],
    ],
];
```

---

## 使用方式

### 文字 → EZPL

```php
use Yii;
use TsengYuChen\BMPtxt\Enums\FontFamily;

// 基本用法
$hex = Yii::$app->bmptxt
    ->text('薄型 SMCG TVS 無鹵')
    ->font(FontFamily::KAIU)
    ->size(12)
    ->dpi(232)
    ->toEzpl(x: 0, y: 0)
    ->toHex();

Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
Yii::$app->response->data   = $hex;
return Yii::$app->response;
```

### 圖片 → 黑白 EZPL

```php
$hex = Yii::$app->bmptxt
    ->image()
    ->fromUrl($request->post('img_url'))
    ->scale((float) $request->post('scale', 1.0))
    ->rotate((float) $request->post('rotate', 0.0))
    ->blackWhite((float) $request->post('bw', 0.8))
    ->toEzpl(
        x: (int) $request->post('x', 0),
        y: (int) $request->post('y', 0),
    )
    ->toHex();
```

---

## IP 白名單保護（assertInternalRequest）

在 Controller Action 的開頭呼叫，若不是內部 IP 則拋出 403 Forbidden：

```php
use Yii;
use yii\web\Controller;

class LabelController extends Controller
{
    /**
     * POST /label/text
     */
    public function actionText(): string
    {
        // 限制只有內部 IP 才能存取
        Yii::$app->bmptxt->assertInternalRequest();

        $text = Yii::$app->request->post('text', '');
        $size = (float) Yii::$app->request->post('size', 12);
        $x    = (int)   Yii::$app->request->post('x', 0);
        $y    = (int)   Yii::$app->request->post('y', 0);

        $hex = Yii::$app->bmptxt
            ->text($text)
            ->size($size)
            ->toEzpl(x: $x, y: $y)
            ->toHex();

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        return $hex;
    }

    /**
     * POST /label/image
     */
    public function actionImage(): string
    {
        Yii::$app->bmptxt->assertInternalRequest();

        $hex = Yii::$app->bmptxt
            ->image()
            ->fromUrl(Yii::$app->request->post('img_url'))
            ->blackWhite((float) Yii::$app->request->post('bw', 0.8))
            ->toEzpl(
                x: (int) Yii::$app->request->post('x', 0),
                y: (int) Yii::$app->request->post('y', 0),
            )
            ->toHex();

        Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
        return $hex;
    }
}
```

### 或使用 Behavior / beforeAction（統一保護）

```php
use TsengYuChen\BMPtxt\Adapters\Yii2\BmptxtComponent;

class LabelController extends Controller
{
    public function behaviors(): array
    {
        return [
            'internalIp' => [
                'class'   => \yii\filters\AccessControl::class,
                'rules'   => [
                    [
                        'allow'     => true,
                        'matchCallback' => function () {
                            $ip = Yii::$app->request->userIP ?? '';
                            /** @var BmptxtComponent $bmptxt */
                            $bmptxt = Yii::$app->bmptxt;
                            return \TsengYuChen\BMPtxt\Support\IpChecker::isAllowed(
                                $ip,
                                $bmptxt->allowedIps
                            );
                        },
                    ],
                ],
            ],
        ];
    }
}
```

---

## 直接傳送到印表機（TCP Socket）

```php
$binary = Yii::$app->bmptxt
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
// config/main.php
'bmptxt' => [
    'class'     => BmptxtComponent::class,
    'fontsPath' => Yii::getAlias('@app') . '/fonts/bmptxt',
],
```
