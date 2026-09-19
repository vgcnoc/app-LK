<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $appLogo = \App\Models\Setting::where('key', 'app_logo')->value('value');
        
        return [
            ...parent::share($request),
            'app_logo' => $appLogo,
            'auth' => [
                'user' => $request->user(),
                'permissions' => $request->user()
                    ? $request->user()->getAllPermissions()->pluck('name')->toArray()
                    : [],
            ],
        ];
    }
}
