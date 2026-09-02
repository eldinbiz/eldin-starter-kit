@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<x-ui::card
    class="w-full max-w-md shadow-theme-lg"
    x-data="loginFlow({
        email: '{{ old('email') }}',
        lookupRoute: '{{ route('login.lookup') }}',
        csrfToken: '{{ csrf_token() }}'
    })"
>
    <div>
        <div class="mb-5 sm:mb-8 text-center sm:text-left">
            <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                Sign In
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                <span x-show="step === 1">Enter your email address to get started</span>
                <span x-show="step === 2" x-cloak>Authenticate with your configured login method</span>
            </p>
        </div>

        @if ($errors->any())
            <x-ui::alert type="error" class="mb-4">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </x-ui::alert>
        @endif

        <div x-show="errorMessage" x-cloak class="mb-4">
            <x-ui::alert type="error">
                <p x-text="errorMessage"></p>
            </x-ui::alert>
        </div>

        {{-- Step 1: Enter Email --}}
        <form @submit.prevent="checkEmail" x-show="step === 1">
            <div class="space-y-5">
                <x-ui::input
                    type="email"
                    id="email"
                    name="email"
                    label="Email Address"
                    required
                    autofocus
                    placeholder="info@gmail.com"
                    x-model="email"
                />

                <div>
                    <x-ui::button
                        type="submit"
                        variant="primary"
                        ::disabled="loading"
                        class="w-full py-3"
                    >
                        <span x-show="!loading">Next &rarr;</span>
                        <span x-show="loading" x-cloak>Checking...</span>
                    </x-ui::button>
                </div>
            </div>
        </form>

        {{-- Step 2: Password / SSO Provider Redirection --}}
        <div x-show="step === 2" x-cloak class="space-y-5">
            <div class="flex items-center justify-between rounded-lg bg-gray-50 p-3 dark:bg-gray-800">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate" x-text="email"></span>
                <button type="button" @click="resetStep" class="text-xs font-semibold text-brand-500 hover:text-brand-600">
                    &larr; Change
                </button>
            </div>

            {{-- Disabled Provider Warning --}}
            <template x-if="!isEnabled && authMethod !== 'local'">
                <x-ui::alert type="warning" class="mb-4">
                    Authentication provider <strong x-text="providerLabel"></strong> is currently disabled by system administrator.
                </x-ui::alert>
            </template>

            {{-- Password Form for Local & LDAP --}}
            <template x-if="authMethod === 'local' || authMethod === 'ldap'">
                <form action="{{ route('login') }}" method="POST" class="space-y-5">
                    @csrf
                    <input type="hidden" name="email" :value="email">

                    <x-ui::input
                        type="password"
                        id="password"
                        name="password"
                        label="Password"
                        required
                        placeholder="Enter your password"
                    />

                    <div class="flex items-center justify-between">
                        <x-ui::checkbox name="remember" id="remember" label="Remember me" />
                    </div>

                    <div>
                        <x-ui::button
                            type="submit"
                            variant="primary"
                            class="w-full py-3"
                        >
                            Sign In
                        </x-ui::button>
                    </div>
                </form>
            </template>

            {{-- SSO / SAML Button for google, microsoft, auth0, saml2 --}}
            <template x-if="authMethod !== 'local' && authMethod !== 'ldap' && isEnabled">
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        This account is configured to authenticate via Single Sign-On (<strong x-text="providerLabel"></strong>).
                    </p>

                    <a
                        :href="'/auth/' + authMethod + '/redirect'"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                    >
                        <span>🌐 Sign In with <span x-text="providerLabel"></span></span>
                    </a>
                </div>
            </template>
        </div>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-brand-500 hover:text-brand-600 font-semibold">
                    Sign Up
                </a>
            </p>
        </div>
    </div>
</x-ui::card>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('loginFlow', (config = {}) => ({
            step: 1,
            email: config.email || '',
            authMethod: 'local',
            providerLabel: '',
            isEnabled: true,
            loading: false,
            errorMessage: '',

            async checkEmail() {
                if (!this.email) return;
                this.loading = true;
                this.errorMessage = '';
                try {
                    const res = await fetch(config.lookupRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        },
                        body: JSON.stringify({ email: this.email })
                    });
                    const data = await res.json();
                    if (data.found) {
                        this.authMethod = data.auth_method;
                        this.providerLabel = data.provider_label;
                        this.isEnabled = data.is_enabled;
                        this.step = 2;
                    } else {
                        this.authMethod = 'local';
                        this.step = 2;
                    }
                } catch (err) {
                    this.errorMessage = 'Failed to lookup account. Please try again.';
                } finally {
                    this.loading = false;
                }
            },

            resetStep() {
                this.step = 1;
                this.errorMessage = '';
            }
        }));
    });
</script>
@endsection
