<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\Yii2;

use yii\base\Component;
use TsengYuChen\BMPtxt\BmpConverter;
use TsengYuChen\BMPtxt\Core\Font\FontManager;
use TsengYuChen\BMPtxt\Core\Renderer\ImageRenderer;
use TsengYuChen\BMPtxt\Core\Renderer\TextRenderer;
use TsengYuChen\BMPtxt\Enums\FontFamily;
use TsengYuChen\BMPtxt\Support\IpChecker;

/**
 * Yii2 Component for BMPtxt.
 *
 * Register in your application config:
 *
 *   'components' => [
 *       'bmptxt' => [
 *           'class'      => \TsengYuChen\BMPtxt\Adapters\Yii2\BmptxtComponent::class,
 *           'fontsPath'  => null,        // null = use bundled fonts
 *           'defaultDpi' => 232,
 *           'allowedIps' => ['127.0.0.1', '192.168.0.0/16'],
 *       ],
 *   ],
 *
 * Usage:
 *   $hex = Yii::$app->bmptxt
 *       ->text('薄型 SMCG TVS 無鹵')
 *       ->size(12)
 *       ->toEzpl()
 *       ->toHex();
 *
 *   // With IP guard
 *   Yii::$app->bmptxt->assertInternalRequest();
 *   $hex = Yii::$app->bmptxt->text('測試')->toEzpl()->toHex();
 *
 * @requires yiisoft/yii2
 */
class BmptxtComponent extends Component
{
    /** Absolute path to fonts directory. null = use package bundled fonts. */
    public ?string $fontsPath = null;

    /** Default output DPI for label printers. */
    public int $defaultDpi = 232;

    /**
     * List of allowed IPs or CIDR ranges for internal-only access.
     * @var string[]
     */
    public array $allowedIps = [
        '127.0.0.1',
        '::1',
        '192.168.0.0/16',
        '10.0.0.0/8',
    ];

    private ?BmpConverter $converter = null;

    public function init(): void
    {
        parent::init();
        $this->converter = BmpConverter::create($this->fontsPath);
    }

    /**
     * Create a TextRenderer pre-configured with optional content.
     */
    public function text(string $content = ''): TextRenderer
    {
        return $this->getConverter()->text($content);
    }

    /**
     * Create an ImageRenderer ready to be configured.
     */
    public function image(): ImageRenderer
    {
        return $this->getConverter()->image();
    }

    /**
     * Get the underlying BmpConverter instance.
     */
    public function getConverter(): BmpConverter
    {
        return $this->converter ??= BmpConverter::create($this->fontsPath);
    }

    /**
     * Get the FontManager (e.g. to list available fonts).
     */
    public function getFontManager(): FontManager
    {
        return $this->getConverter()->getFontManager();
    }

    /**
     * Assert the current request comes from an allowed internal IP.
     * Throws a 403 Forbidden if not.
     *
     * @throws \yii\web\ForbiddenHttpException
     */
    public function assertInternalRequest(): void
    {
        $clientIp = \Yii::$app->request->userIP ?? '';

        if (!IpChecker::isAllowed($clientIp, $this->allowedIps)) {
            throw new \yii\web\ForbiddenHttpException(
                'This endpoint is restricted to internal network access.'
            );
        }
    }
}
