<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Settings\TestAuthProviderAction;
use App\Actions\Settings\UpdateAuthProviderAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\TestAuthProviderRequest;
use App\Http\Requests\Admin\Settings\UpdateAuthProviderRequest;
use App\Models\AuthProviderSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

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
    public function update(
        UpdateAuthProviderRequest $request,
        string $provider,
        UpdateAuthProviderAction $action
    ): RedirectResponse {
        try {
            $action->execute($provider, $request->validated());

            return redirect()->route('admin.settings.auth-providers.index', ['tab' => $provider])
                ->with('success', __(':provider settings updated successfully.', ['provider' => strtoupper($provider)]));
        } catch (InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Test connection parameters for a specific provider.
     */
    public function test(
        TestAuthProviderRequest $request,
        string $provider,
        TestAuthProviderAction $action
    ): JsonResponse {
        try {
            $config = (array) $request->input('settings', []);
            $result = $action->execute($provider, $config);

            return response()->json($result);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
