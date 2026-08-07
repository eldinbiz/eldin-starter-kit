<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\AuthProviderManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function __construct(private AuthProviderManager $authManager)
    {
    }

    /**
     * Show the login screen.
     */
    public function showLogin(): View
    {
        return view('auth.login');
    }

    /**
     * Look up user by email to determine auth_method for 2-step login.
     */
    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = $validated['email'];
        $user = User::where('email', $email)->first();

        if (! $user) {
            return response()->json([
                'found' => false,
                'auth_method' => 'local',
                'is_enabled' => true,
                'message' => __('No user account found with this email.'),
            ]);
        }

        $authMethod = $user->auth_method ?? 'local';
        $isEnabled = $this->authManager->isProviderEnabled($authMethod);

        return response()->json([
            'found' => true,
            'email' => $user->email,
            'name' => $user->name,
            'auth_method' => $authMethod,
            'is_enabled' => $isEnabled,
            'provider_label' => User::AUTH_METHODS[$authMethod] ?? ucfirst($authMethod),
        ]);
    }

    /**
     * Handle authentication attempt (Local or LDAP).
     *
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        $authMethod = $user->auth_method ?? 'local';
        $setting = $this->authManager->getSetting($authMethod);

        if ($authMethod !== 'local' && ($setting === null || ! $setting->is_enabled)) {
            throw ValidationException::withMessages([
                'email' => __("Authentication method [:method] is currently disabled.", ['method' => $authMethod]),
            ]);
        }

        $driver = $this->authManager->driver($authMethod);
        $authenticated = false;

        if ($authMethod === 'local') {
            $remember = $request->boolean('remember');
            $authenticated = Auth::attempt(['email' => $user->email, 'password' => $credentials['password']], $remember);
        } else {
            $authenticated = $driver->authenticate($user, ['password' => $credentials['password']], $setting);

            if ($authenticated) {
                Auth::login($user, $request->boolean('remember'));
            }
        }

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => __('The provided credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();
        $this->setSessionContext($request, Auth::user());

        return redirect()->intended('/');
    }

    /**
     * Redirect to SSO / SAML provider.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $setting = $this->authManager->getSetting($provider);
        $driver = $this->authManager->driver($provider);

        return $driver->redirect($setting);
    }

    /**
     * Handle incoming SSO / SAML provider callback.
     */
    public function callback(Request $request, string $provider): RedirectResponse
    {
        $setting = $this->authManager->getSetting($provider);
        $driver = $this->authManager->driver($provider);

        $user = $driver->handleCallback($request, $setting);

        if (! $user) {
            return redirect()->route('login')->withErrors([
                'email' => __('Authentication failed or user account is not registered.'),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $this->setSessionContext($request, $user);

        return redirect()->intended('/');
    }

    /**
     * Show the registration screen.
     */
    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Handle user registration.
     */
    public function register(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'auth_method' => 'local',
        ]);

        Auth::login($user);
        $this->setSessionContext($request, $user);

        return redirect('/');
    }

    /**
     * Terminate the session and log user out.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Set company and role context in session.
     */
    private function setSessionContext(Request $request, ?User $user): void
    {
        if (! $user) {
            return;
        }

        $company = $user->getActiveCompany();

        if ($company) {
            $request->session()->put('active_company_id', $company->id);

            $activeRole = $user->getActiveRole($company);
            if ($activeRole) {
                $request->session()->put("active_role.{$company->id}", $activeRole->name);
            }
        }
    }
}
