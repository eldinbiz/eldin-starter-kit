@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="relative p-6 sm:p-10 bg-white z-1 dark:bg-gray-900 w-full max-w-md rounded-2xl shadow-theme-lg border border-gray-100 dark:border-gray-800">
    <div>
        <div class="mb-5 sm:mb-8 text-center sm:text-left">
            <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                Sign In
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Enter your email and password to sign in!
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-error-50 p-4 text-sm text-error-600 dark:bg-error-500/10 dark:text-error-400">
                <ul class="list-disc pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="space-y-5">
                <!-- Email -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Email Address <span class="text-error-500">*</span>
                    </label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        placeholder="info@gmail.com"
                        required
                        autofocus
                        class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                    />
                </div>

                <!-- Password -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Password <span class="text-error-500">*</span>
                    </label>
                    <div x-data="{ showPassword: false }" class="relative">
                        <input
                            :type="showPassword ? 'text' : 'password'"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                            class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent py-2.5 pl-4 pr-11 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 dark:focus:border-brand-800"
                        />
                        <span
                            @click="showPassword = !showPassword"
                            class="absolute z-30 text-gray-500 -translate-y-1/2 cursor-pointer right-4 top-1/2 dark:text-gray-400"
                        >
                            <!-- Eye icon -->
                            <svg x-show="!showPassword" class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" />
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 3c-4.42 0-8 3.58-8 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm0 14a6 6 0 1 1 0-12 6 6 0 0 1 0 12z" />
                            </svg>
                            <!-- Eye slash icon -->
                            <svg x-show="showPassword" x-cloak class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z" opacity="0.5"/>
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M2.344 2.343a.75.75 0 0 1 1.06 0l14.253 14.253a.75.75 0 1 1-1.061 1.06L2.344 3.405a.75.75 0 0 1 0-1.062z" />
                            </svg>
                        </span>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center cursor-pointer select-none text-sm text-gray-500 dark:text-gray-400">
                        <div class="relative mr-2.5">
                            <input type="checkbox" name="remember" id="remember" class="sr-only peer" />
                            <div class="box flex h-5 w-5 items-center justify-center rounded border border-gray-300 bg-transparent dark:border-gray-700 peer-checked:border-brand-500 peer-checked:bg-brand-500">
                                <span class="opacity-0 peer-checked:opacity-100 text-white text-xs">✔</span>
                            </div>
                        </div>
                        Remember me
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="flex w-full items-center justify-center rounded-lg bg-brand-500 px-4 py-3 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
                    >
                        Sign In
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Don't have an account?
                <a href="{{ route('register') }}" class="text-brand-500 hover:text-brand-600 font-semibold">
                    Sign Up
                </a>
            </p>
        </div>
    </div>
</div>
@endsection
