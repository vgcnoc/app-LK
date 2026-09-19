<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $users = [];
        $allPermissions = [];
        $roles = [];
        
        if ($request->user()->can('manajemen_pengguna')) {
            $users = \App\Models\User::with('roles')->orderBy('name')->get()->map(function ($user) {
                // Return the role name. Fallback to string role column.
                $user->user_role = $user->roles->first()?->name ?? $user->role;
                return $user;
            });
            $allPermissions = \Spatie\Permission\Models\Permission::orderBy('name')->pluck('name')->toArray();
            $roles = \Spatie\Permission\Models\Role::with('permissions')->get()->map(function($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->toArray(),
                ];
            });
        }
        
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'paymentMethods' => \App\Models\PaymentMethod::orderBy('name')->get(),
            'users' => $users,
            'allPermissions' => $allPermissions,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the global app logo.
     */
    public function updateAppLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'app_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($request->hasFile('app_logo')) {
            $path = $request->file('app_logo')->store('logos', 'public');
            \App\Models\Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => '/storage/' . $path]
            );
        }

        return back()->with('success', 'Logo aplikasi berhasil diperbarui.');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
