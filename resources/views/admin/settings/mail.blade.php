@extends('admin.settings.layout', [
    'title' => 'Mail & Transport Settings',
    'heading' => 'Mail Transport & SMTP Configuration',
    'subheading' => 'Configure email delivery credentials, sender defaults, and verify transport connections with live test emails.',
])

@section('settings-content')
<div x-data="{
    mailer: '{{ old('mail_mailer', $settings['mail_mailer']) }}',
    oauthProvider: '{{ old('mail_oauth_provider', $settings['mail_oauth_provider']) }}',
    smtpOpen: '{{ old('mail_mailer', $settings['mail_mailer']) }}' === 'smtp',
    oauthOpen: '{{ old('mail_mailer', $settings['mail_mailer']) }}' === 'oauth',
    testSending: false,
    testStatus: null,
    testMessage: '',
    selectDriver(driver) {
        this.mailer = driver;
        if (driver === 'smtp') {
            this.smtpOpen = true;
        } else if (driver === 'oauth') {
            this.oauthOpen = true;
        }
    },
    async dispatchTestEmail() {
        this.testSending = true;
        this.testStatus = null;
        try {
            const res = await fetch('{{ route('admin.settings.mail.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                }
            });
            const data = await res.json();
            this.testStatus = res.ok ? 'success' : 'error';
            this.testMessage = data.message || 'Operation completed.';
        } catch (e) {
            this.testStatus = 'error';
            this.testMessage = 'Network error while attempting to send test email.';
        } finally {
            this.testSending = false;
        }
    }
}">
    <!-- Status Alert Banner -->
    <div
        x-show="testStatus === 'success'"
        x-cloak
        class="mb-6 rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400"
    >
        <div class="flex items-center gap-2 font-semibold">
            <span>✓ Dispatch Verified</span>
        </div>
        <p class="mt-1 text-xs" x-text="testMessage"></p>
    </div>

    <div
        x-show="testStatus === 'error'"
        x-cloak
        class="mb-6 rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-800/30 dark:bg-error-500/10 dark:text-error-400"
    >
        <div class="flex items-center gap-2 font-semibold">
            <span>⚠️ Dispatch Error</span>
        </div>
        <p class="mt-1 text-xs" x-text="testMessage"></p>
    </div>

    <form action="{{ route('admin.settings.mail.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Active Driver Selector Card -->
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                    Active Mail Transport Driver
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Select the active transport profile used by the application to send outgoing emails.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <!-- SMTP Option Card -->
                <label
                    @click="selectDriver('smtp')"
                    :class="mailer === 'smtp' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-500/80 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700'"
                    class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 transition-all"
                >
                    <input
                        type="radio"
                        id="mail_mailer_smtp"
                        name="mail_mailer"
                        value="smtp"
                        x-model="mailer"
                        class="mt-1 h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900 dark:text-white">SMTP Transport</span>
                            <span
                                x-show="mailer === 'smtp'"
                                class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400"
                            >
                                ● Active
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Traditional SMTP authentication (Host, Port, Username, Password).
                        </p>
                    </div>
                </label>

                <!-- OAuth Option Card -->
                <label
                    @click="selectDriver('oauth')"
                    :class="mailer === 'oauth' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-500/80 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700'"
                    class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 transition-all"
                >
                    <input
                        type="radio"
                        id="mail_mailer_oauth"
                        name="mail_mailer"
                        value="oauth"
                        x-model="mailer"
                        class="mt-1 h-4 w-4 text-brand-600 focus:ring-brand-500"
                    />
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-sm text-gray-900 dark:text-white">OAuth2 Modern Auth</span>
                            <span
                                x-show="mailer === 'oauth'"
                                class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400"
                            >
                                ● Active
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Modern Identity Auth (Microsoft 365 Exchange, Google Workspace).
                        </p>
                    </div>
                </label>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SMTP COLLAPSIBLE PROFILE CARD              -->
        <!-- ========================================== -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div
                @click="smtpOpen = !smtpOpen"
                class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        📧
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                SMTP Transport Profile
                            </h3>
                            <span
                                x-show="mailer === 'smtp'"
                                class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400"
                            >
                                Active Driver
                            </span>
                            <span
                                x-show="mailer !== 'smtp'"
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                            >
                                Inactive Profile
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Configure standard password-authenticated SMTP server connection details.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        x-show="mailer !== 'smtp'"
                        @click.stop="selectDriver('smtp')"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Set Active
                    </button>
                    <svg
                        class="h-5 w-5 text-gray-400 transition-transform duration-200"
                        :class="smtpOpen ? 'rotate-180' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <div
                x-show="smtpOpen"
                class="border-t border-gray-100 p-6 dark:border-gray-800"
            >
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- SMTP Host -->
                    <div>
                        <label for="mail_host" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Server Host
                        </label>
                        <input
                            type="text"
                            id="mail_host"
                            name="mail_host"
                            value="{{ old('mail_host', $settings['mail_host']) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="e.g. smtp.mailtrap.io or smtp.gmail.com"
                        />
                    </div>

                    <!-- SMTP Port -->
                    <div>
                        <label for="mail_port" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Port
                        </label>
                        <input
                            type="number"
                            id="mail_port"
                            name="mail_port"
                            value="{{ old('mail_port', $settings['mail_port']) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="587, 465, or 2525"
                        />
                    </div>

                    <!-- Encryption Protocol -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Encryption Protocol
                        </label>
                        <div class="flex items-center gap-6 pt-2.5">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="radio"
                                    name="mail_encryption"
                                    value="tls"
                                    {{ old('mail_encryption', $settings['mail_encryption']) === 'tls' ? 'checked' : '' }}
                                    class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                />
                                <span>TLS (STARTTLS / 587)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="radio"
                                    name="mail_encryption"
                                    value="ssl"
                                    {{ old('mail_encryption', $settings['mail_encryption']) === 'ssl' ? 'checked' : '' }}
                                    class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                />
                                <span>SSL (465)</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input
                                    type="radio"
                                    name="mail_encryption"
                                    value="none"
                                    {{ old('mail_encryption', $settings['mail_encryption']) === 'none' ? 'checked' : '' }}
                                    class="h-4 w-4 text-brand-600 focus:ring-brand-500"
                                />
                                <span>None (Unencrypted)</span>
                            </label>
                        </div>
                    </div>

                    <!-- SMTP Username -->
                    <div>
                        <label for="mail_username" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Username
                        </label>
                        <input
                            type="text"
                            id="mail_username"
                            name="mail_username"
                            value="{{ old('mail_username', $settings['mail_username']) }}"
                            autocomplete="off"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="SMTP authentication username"
                        />
                    </div>

                    <!-- SMTP Password -->
                    <div class="md:col-span-2">
                        <label for="mail_password" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Password
                        </label>
                        <input
                            type="password"
                            id="mail_password"
                            name="mail_password"
                            autocomplete="new-password"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="{{ !empty($settings['mail_password']) ? '•••••••••••••••• (Leave blank to keep unchanged)' : 'Enter SMTP password' }}"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Stored securely with AES-256 encryption.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- OAUTH2 COLLAPSIBLE PROFILE CARD            -->
        <!-- ========================================== -->
        <div class="rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div
                @click="oauthOpen = !oauthOpen"
                class="flex cursor-pointer items-center justify-between px-6 py-5 select-none"
            >
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                        🔑
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                                OAuth2 Transport Profile
                            </h3>
                            <span
                                x-show="mailer === 'oauth'"
                                class="inline-flex items-center rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400"
                            >
                                Active Driver
                            </span>
                            <span
                                x-show="mailer !== 'oauth'"
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400"
                            >
                                Inactive Profile
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Configure Modern Identity OAuth2 parameters (Microsoft Graph, XOAUTH2).
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        x-show="mailer !== 'oauth'"
                        @click.stop="selectDriver('oauth')"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        Set Active
                    </button>
                    <svg
                        class="h-5 w-5 text-gray-400 transition-transform duration-200"
                        :class="oauthOpen ? 'rotate-180' : ''"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            <div
                x-show="oauthOpen"
                class="border-t border-gray-100 p-6 dark:border-gray-800"
            >
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- OAuth Provider -->
                    <div class="md:col-span-2">
                        <label for="mail_oauth_provider" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            OAuth Provider <span class="text-error-500">*</span>
                        </label>
                        <select
                            id="mail_oauth_provider"
                            name="mail_oauth_provider"
                            x-model="oauthProvider"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="microsoft365">Microsoft 365 / Office 365 Exchange (Microsoft Graph API)</option>
                            <option value="google">Google Workspace / Gmail (XOAUTH2)</option>
                            <option value="generic">Generic / Custom OAuth2 (XOAUTH2 SMTP)</option>
                        </select>
                    </div>

                    <!-- Tenant ID (Microsoft 365) -->
                    <div x-show="oauthProvider === 'microsoft365'">
                        <label for="mail_oauth_tenant_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Directory (Tenant) ID
                        </label>
                        <input
                            type="text"
                            id="mail_oauth_tenant_id"
                            name="mail_oauth_tenant_id"
                            value="{{ old('mail_oauth_tenant_id', $settings['mail_oauth_tenant_id']) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="e.g. common, organizations, or Azure Tenant GUID"
                        />
                    </div>

                    <!-- Client ID (Both) -->
                    <div>
                        <label for="mail_oauth_client_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Application (Client) ID <span class="text-error-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="mail_oauth_client_id"
                            name="mail_oauth_client_id"
                            value="{{ old('mail_oauth_client_id', $settings['mail_oauth_client_id']) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="Client / Application GUID"
                        />
                    </div>

                    <!-- Client Secret (Both) -->
                    <div>
                        <label for="mail_oauth_client_secret" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Client Secret
                        </label>
                        <input
                            type="password"
                            id="mail_oauth_client_secret"
                            name="mail_oauth_client_secret"
                            autocomplete="new-password"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="{{ !empty($settings['mail_oauth_client_secret']) ? '•••••••••••••••• (Leave blank to keep unchanged)' : 'Enter OAuth client secret' }}"
                        />
                        <p class="mt-1 text-xs text-gray-500">
                            Encrypted AES-256 in database.
                        </p>
                    </div>

                    <!-- Refresh Token (Google / Generic) -->
                    <div x-show="oauthProvider === 'google' || oauthProvider === 'generic'">
                        <label for="mail_oauth_refresh_token" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            OAuth2 Refresh Token
                        </label>
                        <input
                            type="password"
                            id="mail_oauth_refresh_token"
                            name="mail_oauth_refresh_token"
                            autocomplete="new-password"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="{{ !empty($settings['mail_oauth_refresh_token']) ? '•••••••••••••••• (Leave blank to keep unchanged)' : 'Enter refresh token' }}"
                        />
                    </div>

                    <!-- Custom Token URL (Generic) -->
                    <div x-show="oauthProvider === 'generic'">
                        <label for="mail_oauth_token_url" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            OAuth Token Endpoint URL
                        </label>
                        <input
                            type="url"
                            id="mail_oauth_token_url"
                            name="mail_oauth_token_url"
                            value="{{ old('mail_oauth_token_url', $settings['mail_oauth_token_url']) }}"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            placeholder="https://idp.example.com/oauth/token"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- GLOBAL SENDER DEFAULTS                     -->
        <!-- ========================================== -->
        <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                    Global Sender Defaults
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Default "From" address and display name used across all outgoing system notifications.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <!-- From Address -->
                <div>
                    <label for="mail_from_address" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Sender Email Address (From) <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="mail_from_address"
                        name="mail_from_address"
                        value="{{ old('mail_from_address', $settings['mail_from_address']) }}"
                        required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="noreply@yourdomain.com"
                    />
                </div>

                <!-- From Name -->
                <div>
                    <label for="mail_from_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Sender Display Name (From) <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="mail_from_name"
                        name="mail_from_name"
                        value="{{ old('mail_from_name', $settings['mail_from_name']) }}"
                        required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="Scaffolding App"
                    />
                </div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
            <button
                type="button"
                @click="dispatchTestEmail()"
                :disabled="testSending"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-gray-500/10 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <span x-show="testSending" class="animate-spin">⏳</span>
                <span>✉️</span>
                <span x-text="testSending ? 'Dispatching...' : 'Send Test Email'"></span>
            </button>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/50"
            >
                Save Mail Settings
            </button>
        </div>
    </form>
</div>
@endsection
