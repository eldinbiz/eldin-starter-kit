<div class="space-y-6">
    {{-- Status Alert Banners --}}
    @if ($flashSuccess)
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    @if ($testStatus === 'success')
        <div class="rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <span>✓ Dispatch Verified</span>
                </div>
                <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs">{{ $testMessage }}</p>
        </div>
    @endif

    @if ($testStatus === 'error' || $flashError)
        <div class="rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-800/30 dark:bg-error-500/10 dark:text-error-400">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2 font-semibold">
                    <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <span>⚠️ Connection / Error</span>
                </div>
                <button type="button" wire:click="dismissFlash" class="text-error-600 hover:text-error-800 dark:text-error-400 dark:hover:text-error-200 cursor-pointer">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
            <p class="mt-1 text-xs">{{ $testMessage ?: $flashError }}</p>
        </div>
    @endif

    {{-- Active Driver Selector Card --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                Active Mail Transport Driver
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Select the active transport profile used by the application to send outgoing emails.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            {{-- SMTP Option Card --}}
            <button
                type="button"
                wire:click="selectMailer('smtp')"
                class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 text-left transition-all {{ $mail_mailer === 'smtp' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-500/80 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700' }}"
            >
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm text-gray-900 dark:text-white">SMTP Transport</span>
                        @if ($mail_mailer === 'smtp')
                            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                ● Active
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Traditional SMTP (Host, Port, Username, Password).
                    </p>
                </div>
            </button>

            {{-- OAuth Option Card --}}
            <button
                type="button"
                wire:click="selectMailer('oauth')"
                class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 text-left transition-all {{ $mail_mailer === 'oauth' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-500/80 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700' }}"
            >
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm text-gray-900 dark:text-white">OAuth2 Modern Auth</span>
                        @if ($mail_mailer === 'oauth')
                            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                ● Active
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Microsoft 365, Google Workspace, XOAUTH2 tokens.
                    </p>
                </div>
            </button>

            {{-- Log Driver Card --}}
            <button
                type="button"
                wire:click="selectMailer('log')"
                class="relative flex cursor-pointer items-start gap-4 rounded-xl border p-4 text-left transition-all {{ $mail_mailer === 'log' ? 'border-brand-500 bg-brand-50/50 ring-2 ring-brand-500/20 dark:border-brand-500/80 dark:bg-brand-500/10' : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-800 dark:bg-gray-900/50 dark:hover:border-gray-700' }}"
            >
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-sm text-gray-900 dark:text-white">Log / Development</span>
                        @if ($mail_mailer === 'log')
                            <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                ● Active
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Writes outgoing emails to laravel.log (No delivery).
                    </p>
                </div>
            </button>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- SMTP Settings Section --}}
        @if ($mail_mailer === 'smtp')
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        SMTP Server Credentials
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure host endpoint, authentication, and port details.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    {{-- SMTP Host --}}
                    <div>
                        <label for="mail_host" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Host <span class="text-error-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="mail_host"
                            wire:model="mail_host"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="e.g. smtp.mailgun.org, smtp.sendgrid.net"
                        />
                        @error('mail_host') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- SMTP Port --}}
                    <div>
                        <label for="mail_port" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Port <span class="text-error-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="mail_port"
                            wire:model="mail_port"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="587, 465, or 25"
                        />
                        @error('mail_port') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                    </div>

                    {{-- SMTP Username --}}
                    <div>
                        <label for="mail_username" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            SMTP Username
                        </label>
                        <input
                            type="text"
                            id="mail_username"
                            wire:model="mail_username"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="username or API key"
                        />
                    </div>

                    {{-- SMTP Password --}}
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="mail_password" class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                                SMTP Password / Key
                            </label>
                            <button
                                type="button"
                                wire:click="toggleShowPassword"
                                class="text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 cursor-pointer"
                            >
                                {{ $showPassword ? 'Hide password' : 'Show password' }}
                            </button>
                        </div>
                        <input
                            type="{{ $showPassword ? 'text' : 'password' }}"
                            id="mail_password"
                            wire:model="mail_password"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            placeholder="Leave blank to keep existing password"
                        />
                    </div>

                    {{-- Encryption --}}
                    <div>
                        <label for="mail_encryption" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Encryption Protocol
                        </label>
                        <select
                            id="mail_encryption"
                            wire:model="mail_encryption"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="tls">TLS (STARTTLS - Recommended for 587)</option>
                            <option value="ssl">SSL (Implicit TLS - Recommended for 465)</option>
                            <option value="none">None (Plaintext - Port 25)</option>
                        </select>
                    </div>
                </div>
            </div>
        @endif

        {{-- OAuth Settings Section --}}
        @if ($mail_mailer === 'oauth')
            <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        OAuth2 Credentials
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Configure Microsoft 365 or Google OAuth client credentials.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">OAuth Provider</label>
                        <select
                            wire:model="mail_oauth_provider"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="microsoft365">Microsoft 365 / Azure AD</option>
                            <option value="google">Google Workspace</option>
                            <option value="generic">Generic XOAUTH2</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tenant ID</label>
                        <input
                            type="text"
                            wire:model="mail_oauth_tenant_id"
                            placeholder="e.g. common or Azure Directory ID"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Client ID / App ID</label>
                        <input
                            type="text"
                            wire:model="mail_oauth_client_id"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Client Secret</label>
                        <input
                            type="password"
                            wire:model="mail_oauth_client_secret"
                            placeholder="Leave blank to keep existing secret"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>
                </div>
            </div>
        @endif

        {{-- Sender Identity Section --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                    Sender Identity Defaults
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Default "From" header address and display name attached to all outgoing emails.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label for="mail_from_address" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Default From Email Address <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="mail_from_address"
                        wire:model="mail_from_address"
                        required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="no-reply@yourcompany.com"
                    />
                    @error('mail_from_address') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="mail_from_name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Default From Display Name <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="mail_from_name"
                        wire:model="mail_from_name"
                        required
                        class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        placeholder="e.g. Scaffolding Platform"
                    />
                    @error('mail_from_name') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Save Button --}}
        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800 gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/50 cursor-pointer"
            >
                <span wire:loading.remove wire:target="save">Save Mail Settings</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>

    {{-- Live Connection Tester Section --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                Live Mail Connection Tester
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Dispatch an immediate test email to verify credentials and SMTP connectivity in real time.
            </p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <input
                type="email"
                wire:model="test_recipient"
                placeholder="recipient@example.com"
                class="h-11 flex-1 rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <button
                type="button"
                wire:click="sendTestEmail"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-gray-800 px-5 py-2.5 text-sm font-medium text-white hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 shadow-theme-xs cursor-pointer"
            >
                <span wire:loading.remove wire:target="sendTestEmail">🚀 Send Live Test Email</span>
                <span wire:loading wire:target="sendTestEmail" class="flex items-center gap-2">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    Dispatching Test...
                </span>
            </button>
        </div>
    </div>
</div>
