<?php

declare(strict_types=1);

namespace TsengYuChen\BMPtxt\Adapters\Laravel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TsengYuChen\BMPtxt\Support\IpChecker;

/**
 * Laravel Middleware: restrict access to internal IPs only.
 *
 * Replaces the original is_external() check from the legacy codebase.
 *
 * Register in your routes:
 *   Route::middleware(InternalIpMiddleware::class)->group(function () {
 *       Route::post('/label/text', [LabelController::class, 'text']);
 *   });
 *
 * Or register globally as a named middleware in bootstrap/app.php (Laravel 11+):
 *   ->withMiddleware(function (Middleware $middleware) {
 *       $middleware->alias(['internal.ip' => InternalIpMiddleware::class]);
 *   })
 */
class InternalIpMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var string[] $allowedIps */
        $allowedIps = config('bmptxt.allowed_ips', ['127.0.0.1', '::1']);
        $clientIp   = $request->ip() ?? '';

        if (!IpChecker::isAllowed($clientIp, $allowedIps)) {
            abort(403, 'Forbidden: this endpoint is restricted to internal network access.');
        }

        return $next($request);
    }
}
