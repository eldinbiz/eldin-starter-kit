@extends('admin.settings.layout', [
    'title' => 'Mail & Transport Settings',
    'heading' => 'Mail Transport & SMTP Configuration',
    'subheading' => 'Configure email delivery credentials, sender defaults, and verify SMTP connection with live test emails.',
])

@section('settings-content')
<div x-data="{
    testModalOpen: false,
    testEmail: '',
    testSending: false,
    testStatus: null,
    testMessage: '',
    async dispatchTestEmail() {
        if (!this.testEmail) return;
        this.testSending = true;
        this.testStatus = null;
        try {
            const res = await fetch('{{ route('admin.settings.mail.test') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ recipient_email: this.testEmail })
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
    <form action="{{ route('admin.settings.mail.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <!-- Mail Driver -->
            <div>
                <label for="mail_mailer" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Mail Driver / Transport <span class="text-error-500">*</span>
                </label>
                <select
                    id="mail_mailer"
                    name="mail_mailer"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    <option value="smtp" {{ old('mail_mailer', $settings['mail_mailer']) === 'smtp' ? 'selected' : '' }}>SMTP (Recommended)</option>
                    <option value="sendmail" {{ old('mail_mailer', $settings['mail_mailer']) === 'sendmail' ? 'selected' : '' }}>Sendmail</option>
                    <option value="mailgun" {{ old('mail_mailer', $settings['mail_mailer']) === 'mailgun' ? 'selected' : '' }}>Mailgun</option>
                    <option value="ses" {{ old('mail_mailer', $settings['mail_mailer']) === 'ses' ? 'selected' : '' }}>Amazon SES</option>
                    <option value="postmark" {{ old('mail_mailer', $settings['mail_mailer']) === 'postmark' ? 'selected' : '' }}>Postmark</option>
                    <option value="log" {{ old('mail_mailer', $settings['mail_mailer']) === 'log' ? 'selected' : '' }}>Log (Local Debugging)</option>
                    <option value="array" {{ old('mail_mailer', $settings['mail_mailer']) === 'array' ? 'selected' : '' }}>Array (Unit Testing)</option>
                </select>
            </div>

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
            <div>
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

        <!-- Action Bar -->
        <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
            <button
                type="button"
                @click="testModalOpen = true; testStatus = null; testMessage = ''"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 focus:outline-hidden focus:ring-3 focus:ring-gray-500/10 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <span>✉️</span>
                <span>Send Test Email</span>
            </button>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-3 focus:ring-brand-500/50"
            >
                Save Mail Settings
            </button>
        </div>
    </form>

    <!-- Test Email Modal (Alpine.js) -->
    <div
        x-show="testModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="testModalOpen = false"
    >
        <div
            @click.outside="testModalOpen = false"
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="mb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                    Send Live Test Email
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Verify that your configured SMTP transport credentials and TLS handshake can successfully dispatch emails.
                </p>
            </div>

            <!-- Result Alert -->
            <div
                x-show="testStatus === 'success'"
                x-cloak
                class="mb-4 rounded-lg bg-success-50 p-3 text-xs text-success-600 dark:bg-success-500/10 dark:text-success-400"
                x-text="testMessage"
            ></div>

            <div
                x-show="testStatus === 'error'"
                x-cloak
                class="mb-4 rounded-lg bg-error-50 p-3 text-xs text-error-600 dark:bg-error-500/10 dark:text-error-400"
                x-text="testMessage"
            ></div>

            <div class="space-y-4">
                <div>
                    <label for="test_recipient_email" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Recipient Email Address
                    </label>
                    <input
                        type="email"
                        id="test_recipient_email"
                        x-model="testEmail"
                        placeholder="you@example.com"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="testModalOpen = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="dispatchTestEmail()"
                    :disabled="!testEmail || testSending"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 disabled:opacity-50"
                >
                    <span x-show="testSending" class="animate-spin">⏳</span>
                    <span x-text="testSending ? 'Dispatching...' : 'Send Test Mail'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
