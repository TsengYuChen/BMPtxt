<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Config\Bmptxt as BmptxtConfig;
use TsengYuChen\BMPtxt\Support\IpChecker;

/**
 * CodeIgniter 4 Filter: restrict routes to internal IPs only.
 *
 * Replaces the original is_external() check from the legacy BMPtext/BMPimage controllers.
 *
 * Register in app/Config/Filters.php:
 *
 *   use TsengYuChen\BMPtxt\Adapters\CodeIgniter4\Filters\InternalIpFilter;
 *
 *   public array $aliases = [
 *       'internal.ip' => InternalIpFilter::class,
 *   ];
 *
 * Then apply to routes in app/Config/Routes.php:
 *
 *   $routes->group('label', ['filter' => 'internal.ip'], function ($routes) {
 *       $routes->post('text',  'LabelController::text');
 *       $routes->post('image', 'LabelController::image');
 *   });
 *
 * @requires codeigniter4/framework
 */
class InternalIpFilter implements FilterInterface
{
    /**
     * @param mixed[]|null $arguments
     * @return ResponseInterface|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        /** @var BmptxtConfig $config */
        $config     = config('Bmptxt') ?? new BmptxtConfig();
        $allowedIps = $config->allowedIps;
        $clientIp   = $request->getIPAddress();

        if (!IpChecker::isAllowed($clientIp, $allowedIps)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody('Forbidden: this endpoint is restricted to internal network access.');
        }
    }

    /**
     * @param mixed[]|null $arguments
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ResponseInterface
    {
        return $response;
    }
}
