<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthProviderSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthProviderSettingController extends Controller
{
    /**
     * Display tabbed authentication provider configuration page.
     */
    public function index(Request $request): View
    {
        $providers = ['local', 'ldap', 'google', 'microsoft', 'auth0', 'saml2'];
        $settings = AuthProviderSetting::whereIn('provider', $providers)
            ->get()
            ->keyBy('provider');

        $activeTab = $request->query('tab', 'local');
        if (! in_array($activeTab, $providers, true)) {
            $activeTab = 'local';
        }

        return view('admin.settings.auth-providers', [
            'providers' => $providers,
            'settings' => $settings,
            'activeTab' => $activeTab,
        ]);
    }

    /**
     * Update configuration for a specific provider.
     */
    public function update(Request $request, string $provider): RedirectResponse
    {
        $validProviders = ['ldap', 'google', 'microsoft', 'auth0', 'saml2'];

        if (! in_array($provider, $validProviders, true)) {
            return redirect()->back()->with('error', __('Invalid provider selected.'));
        }

        $validated = $request->validate([
            'is_enabled' => ['nullable', 'boolean'],
            'allow_auto_provisioning' => ['nullable', 'boolean'],
            'settings' => ['nullable', 'array'],
        ]);

        AuthProviderSetting::updateOrCreate(
            ['provider' => $provider],
            [
                'is_enabled' => $request->boolean('is_enabled'),
                'allow_auto_provisioning' => $request->boolean('allow_auto_provisioning'),
                'settings' => $validated['settings'] ?? [],
            ]
        );

        return redirect()->route('admin.settings.auth-providers.index', ['tab' => $provider])
            ->with('success', __(':provider settings updated successfully.', ['provider' => strtoupper($provider)]));
    }

    /**
     * Test connection parameters for a specific provider.
     */
    public function test(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        $validProviders = ['local', 'ldap', 'google', 'microsoft', 'auth0', 'saml2'];

        if (! in_array($provider, $validProviders, true)) {
            return response()->json([
                'success' => false,
                'message' => __('Invalid provider selected.'),
            ], 422);
        }

        $config = $request->input('settings', []);
        if (empty($config)) {
            $savedSetting = AuthProviderSetting::forProvider($provider);
            $config = $savedSetting->settings ?? [];
        }

        $authManager = app(\App\Services\Auth\AuthProviderManager::class);
        $driver = $authManager->driver($provider);

        $result = $driver->testConnection($config);

        return response()->json($result);
    }
}
