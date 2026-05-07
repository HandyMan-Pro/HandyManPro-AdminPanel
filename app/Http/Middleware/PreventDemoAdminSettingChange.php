<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Setting;
/**
 * Prevents demo admin (e.g. demo@admin.com) from changing protected settings.
 * When the authenticated user has the demo_admin role, any request to the
 * protected setting routes is blocked (403 / redirect with error).
 */
class PreventDemoAdminSettingChange
{
    /**
     * Route names that demo admin cannot modify (save/update/delete).
     * role_layout_page and layout_page are not listed so demo admin can VIEW
     * role & permission content and setting tab content.
     */
    protected static array $protectedSettingRouteNames = [
        'generalsetting',
        'seosetting',
        'themesetup',
        'sitesetup',
        'serviceConfig',
        'otherSetting',
        'socialMedia',
        'cookiesetup',
        'saveLangContent',
        'paymentsettingsUpdates',
        'envSetting',
        // 'saveEarningTypeSetting',
        'permission.save',
        'permission.store',
        'role.store',
        'role.update',
        'role.destroy',
        'role.bulk-action',
        
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return $next($request);
        }

        if (! $request->user()->hasRole('demo_admin')) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // Block role status change via changeStatus?type=role (AJAX expects status + message)
        if ($routeName === 'changeStatus' && $request->get('type') === 'role') {
            return response()->json([
                'status'  => false,
                'message' => __('messages.demo_setting_restricted'),
            ]);
        }
        $demoLogin = json_decode(
            Setting::where('type','OTHER_SETTING')->value('value'),
            true
        )['demo_login'] ?? 0;
        if ($routeName && in_array($routeName, self::$protectedSettingRouteNames, true) && $demoLogin ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => __('messages.demo_setting_restricted'),
                ], 403);
            }

            return redirect()->back()
                ->with('error', __('messages.demo_setting_restricted'));
        }

        return $next($request);
    }
}
