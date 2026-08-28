@extends('layouts.app')

@section('title', 'Authentication Provider Settings')

@section('content')
<div x-data="{ pageName: 'System Configuration' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6" x-data="{ activeTab: '{{ $activeTab }}' }">
    <div class="mb-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
            Authentication Methods Configuration
        </h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Configure system-wide settings for authentication providers and auto-provisioning rules.
        </p>
    </div>

    @if (session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-600 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Nav Tabs --}}
    <div class="mb-6 border-b border-gray-200 dark:border-gray-800">
        <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
            <button
                @click="activeTab = 'local'"
                :class="activeTab === 'local' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                Local
            </button>
            <button
                @click="activeTab = 'ldap'"
                :class="activeTab === 'ldap' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                LDAP
            </button>
            <button
                @click="activeTab = 'google'"
                :class="activeTab === 'google' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                Google
            </button>
            <button
                @click="activeTab = 'microsoft'"
                :class="activeTab === 'microsoft' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                Microsoft
            </button>
            <button
                @click="activeTab = 'auth0'"
                :class="activeTab === 'auth0' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                Auth0
            </button>
            <button
                @click="activeTab = 'saml2'"
                :class="activeTab === 'saml2' ? 'border-brand-500 text-brand-600 dark:text-brand-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors"
            >
                SAML 2.0
            </button>
        </nav>
    </div>

    {{-- Tab Contents --}}

    {{-- Local Tab --}}
    <div 
        x-show="activeTab === 'local'" 
        x-cloak 
        x-data="authProviderTester({
            testUrl: '{{ route('admin.settings.auth-providers.test', 'local') }}',
            csrfToken: '{{ csrf_token() }}'
        })"
    >
        <div class="rounded-xl border border-brand-200 bg-brand-50/50 p-5 dark:border-brand-900/40 dark:bg-brand-950/20 mb-6">
            <div class="flex items-start gap-3">
                <span class="text-brand-500">ℹ️</span>
                <div>
                    <h4 class="font-medium text-gray-800 dark:text-white/90">Local Authentication</h4>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        authentication will refer to user account data. no configuration necessary
                    </p>
                </div>
            </div>
        </div>

        <div x-show="testResult !== null" x-cloak class="mb-4">
            <div
                :class="testResult && testResult.success ? 'bg-success-50 text-success-700 border-success-200 dark:bg-success-500/10 dark:text-success-400 dark:border-success-500/20' : 'bg-error-50 text-error-700 border-error-200 dark:bg-error-500/10 dark:text-error-400 dark:border-error-500/20'"
                class="rounded-lg border p-4 text-sm font-medium flex items-center justify-between"
            >
                <div class="flex items-center gap-2">
                    <span x-text="testResult && testResult.success ? '✅' : '❌'"></span>
                    <span x-text="testResult ? testResult.message : ''"></span>
                </div>
                <button type="button" @click="testResult = null" class="text-xs opacity-60 hover:opacity-100">&times;</button>
            </div>
        </div>

        <div>
            <button
                type="button"
                @click="testConnection()"
                :disabled="testing"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <span x-text="testing ? 'Testing Connection...' : 'Test Connection'"></span>
            </button>
        </div>
    </div>

    {{-- Provider Form Template Component for LDAP, Google, Microsoft, Auth0, SAML2 --}}
    @foreach (['ldap', 'google', 'microsoft', 'auth0', 'saml2'] as $prov)
        @php
            $setting = $settings->get($prov);
            $isEnabled = old('is_enabled', $setting?->is_enabled ?? false);
            $autoProvision = old('allow_auto_provisioning', $setting?->allow_auto_provisioning ?? false);
            $configData = $setting?->settings ?? [];
        @endphp
        <div x-show="activeTab === '{{ $prov }}'" x-cloak>
            <form
                x-data="authProviderTester({
                    testUrl: '{{ route('admin.settings.auth-providers.test', $prov) }}',
                    csrfToken: '{{ csrf_token() }}'
                })"
                action="{{ route('admin.settings.auth-providers.update', $prov) }}"
                method="POST"
                class="space-y-6"
            >
                @csrf
                @method('PUT')

                {{-- Toggles --}}
                <div class="space-y-4 rounded-xl border border-gray-200 p-5 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-800 dark:text-white/90">Enable Provider</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Allow users to log in using {{ strtoupper($prov) }}</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="is_enabled" value="1" class="peer sr-only" {{ $isEnabled ? 'checked' : '' }}>
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-500 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"></div>
                        </label>
                    </div>
                    {{-- for now, this is not used. hide this for future development --}}
                    <div class="hidden border-t border-gray-100 pt-4 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <span class="font-medium text-gray-800 dark:text-white/90">Auto-Provisioning (JIT)</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Automatically create local user accounts for new authenticated SSO users</p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input type="checkbox" name="allow_auto_provisioning" value="1" class="peer sr-only" {{ $autoProvision ? 'checked' : '' }}>
                            <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-500 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"></div>
                        </label>
                    </div>
                </div>

                {{-- Provider Fields --}}
                @if ($prov === 'ldap')
                    <div class="space-y-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">LDAP Host</label>
                                <input type="text" name="settings[host]" value="{{ old('settings.host', $configData['host'] ?? '') }}" placeholder="ldap.example.com" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Port</label>
                                <input type="number" name="settings[port]" value="{{ old('settings.port', $configData['port'] ?? 389) }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">User DN Format</label>
                                <input type="text" name="settings[user_dn_format]" value="{{ old('settings.user_dn_format', $configData['user_dn_format'] ?? 'uid=%s,%s') }}" placeholder="uid=%s,%s" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Encryption</label>
                                <select name="settings[encryption]" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                    <option value="none" {{ ($configData['encryption'] ?? '') === 'none' ? 'selected' : '' }}>None</option>
                                    <option value="tls" {{ ($configData['encryption'] ?? '') === 'tls' ? 'selected' : '' }}>TLS</option>
                                    <option value="ssl" {{ ($configData['encryption'] ?? '') === 'ssl' ? 'selected' : '' }}>SSL</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Base DN</label>
                            <input type="text" name="settings[base_dn]" value="{{ old('settings.base_dn', $configData['base_dn'] ?? '') }}" placeholder="dc=example,dc=com" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                    </div>
                @elseif (in_array($prov, ['google', 'microsoft', 'auth0'], true))
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Client ID</label>
                            <input type="text" name="settings[client_id]" value="{{ old('settings.client_id', $configData['client_id'] ?? '') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Client Secret</label>
                            <input type="password" name="settings[client_secret]" value="{{ old('settings.client_secret', $configData['client_secret'] ?? '') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                        @if ($prov === 'microsoft')
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tenant ID</label>
                                <input type="text" name="settings[tenant_id]" value="{{ old('settings.tenant_id', $configData['tenant_id'] ?? 'common') }}" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                            </div>
                        @endif
                        @if ($prov === 'auth0')
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Domain</label>
                                <input type="text" name="settings[domain]" value="{{ old('settings.domain', $configData['domain'] ?? '') }}" placeholder="your-tenant.auth0.com" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                            </div>
                        @endif
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Redirect Callback URL (Read-only)</label>
                            <input type="text" readonly value="{{ route('auth.callback', ['provider' => $prov]) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        </div>
                    </div>
                @elseif ($prov === 'saml2')
                    <div class="space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">IdP Metadata URL (Recommended)</label>
                            <input type="url" name="settings[metadata_url]" value="{{ old('settings.metadata_url', $configData['metadata_url'] ?? '') }}" placeholder="https://idp.example.com/metadata/xml" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">IdP Entity ID</label>
                            <input type="text" name="settings[entity_id]" value="{{ old('settings.entity_id', $configData['entity_id'] ?? '') }}" placeholder="http://saml.example.com/trust" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">IdP Single Sign-On (ACS) URL</label>
                            <input type="url" name="settings[sso_url]" value="{{ old('settings.sso_url', $configData['sso_url'] ?? '') }}" placeholder="https://idp.example.com/auth/acs" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">IdP Certificate (X.509 String)</label>
                            <textarea name="settings[certificate]" rows="3" placeholder="MIIC4jCCAcqgAwIBAgIQbDO5YO..." class="w-full rounded-lg border border-gray-300 bg-transparent p-3 text-sm dark:border-gray-700 dark:text-white">{{ old('settings.certificate', $configData['certificate'] ?? '') }}</textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Assertion Consumer Service (ACS) Callback URL (Read-only)</label>
                            <input type="text" readonly value="{{ route('auth.callback', ['provider' => 'saml2']) }}" class="h-11 w-full rounded-lg border border-gray-200 bg-gray-100 px-4 py-2.5 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                        </div>
                    </div>
                @endif

                {{-- Inline Test Result Alert --}}
                <div x-show="testResult !== null" x-cloak class="transition-all">
                    <div
                        :class="testResult && testResult.success ? 'bg-success-50 text-success-700 border-success-200 dark:bg-success-500/10 dark:text-success-400 dark:border-success-500/20' : 'bg-error-50 text-error-700 border-error-200 dark:bg-error-500/10 dark:text-error-400 dark:border-error-500/20'"
                        class="rounded-lg border p-4 text-sm font-medium flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span x-text="testResult && testResult.success ? '✅' : '❌'"></span>
                            <span x-text="testResult ? testResult.message : ''"></span>
                        </div>
                        <button type="button" @click="testResult = null" class="text-xs opacity-60 hover:opacity-100">&times;</button>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-4">
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
                        Save {{ strtoupper($prov) }} Settings
                    </button>

                    <button
                        type="button"
                        @click="testConnection()"
                        :disabled="testing"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <span x-text="testing ? 'Testing Connection...' : 'Test Connection'"></span>
                    </button>
                </div>
            </form>
        </div>
    @endforeach
</div>

<script>
    (function () {
        const registerTester = () => {
            if (typeof Alpine === 'undefined') return;

            Alpine.data('authProviderTester', (config = {}) => ({
                testing: false,
                testResult: null,
                async testConnection() {
                    this.testing = true;
                    this.testResult = null;
                    let body = null;
                    let headers = {
                        'X-CSRF-TOKEN': config.csrfToken,
                        'Accept': 'application/json'
                    };

                    if (this.$el.tagName && this.$el.tagName.toLowerCase() === 'form') {
                        const formData = new FormData(this.$el);
                        formData.delete('_method');
                        body = formData;
                    }

                    try {
                        const response = await fetch(config.testUrl, {
                            method: 'POST',
                            headers: headers,
                            body: body
                        });
                        const contentType = response.headers.get('content-type') || '';
                        if (contentType.includes('application/json')) {
                            const data = await response.json();
                            if (!response.ok && !data.message) {
                                data.message = `HTTP Error ${response.status}: ${response.statusText}`;
                            }
                            this.testResult = data;
                        } else {
                            const text = await response.text();
                            this.testResult = { success: false, message: `Server Error (${response.status}): ${text.substring(0, 200)}` };
                        }
                    } catch (err) {
                        this.testResult = { success: false, message: `Connection test error: ${err.message || err}` };
                    } finally {
                        this.testing = false;
                    }
                }
            }));
        };

        if (window.Alpine) {
            registerTester();
        } else {
            document.addEventListener('alpine:init', registerTester);
        }
    })();
</script>
@endsection
