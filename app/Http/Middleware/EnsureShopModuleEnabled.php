<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureShopModuleEnabled
{
    /**
     * Handle an incoming request. Abort 403 if Shop module is disabled in service configurations.
     * Returns JSON response for API requests, HTML for web requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $setting = Setting::where('type', 'service-configurations')
            ->where('key', 'service-configurations')
            ->first();

        if ($setting && !empty($setting->value)) {
            $config = json_decode($setting->value);
            if (isset($config->shop) && (int) $config->shop !== 1) {
                // Return JSON response for API requests
                if ($request->is('api/*') || $request->expectsJson()) {
                    return response()->json([
                        'status' => false,
                        'message' => __('messages.permission_denied')
                    ], 403);
                }
                // Return HTML error for web requests
                abort(403, __('messages.permission_denied'));
            }
        }

        return $next($request);
    }
}
