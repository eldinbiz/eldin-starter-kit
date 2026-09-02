@extends('layouts.auth')

@section('title', 'Sign Up')

@section('content')
<x-ui::card class="w-full max-w-md shadow-theme-lg">
    <div>
        <div class="mb-5 sm:mb-8 text-center sm:text-left">
            <h1 class="mb-2 font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
                Sign Up
            </h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Enter your details to create an account!
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

        <form action="{{ route('register') }}" method="POST">
            @csrf
            <div class="space-y-5">
                {{-- Full Name --}}
                <x-ui::input
                    type="text"
                    id="name"
                    name="name"
                    label="Full Name"
                    value="{{ old('name') }}"
                    placeholder="John Doe"
                    required
                    autofocus
                />

                {{-- Email --}}
                <x-ui::input
                    type="email"
                    id="email"
                    name="email"
                    label="Email Address"
                    value="{{ old('email') }}"
                    placeholder="john@example.com"
                    required
                />

                {{-- Password --}}
                <x-ui::input
                    type="password"
                    id="password"
                    name="password"
                    label="Password"
                    placeholder="Min. 8 characters"
                    required
                />

                {{-- Confirm Password --}}
                <x-ui::input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    label="Confirm Password"
                    placeholder="Re-enter your password"
                    required
                />

                {{-- Submit Button --}}
                <div>
                    <x-ui::button
                        type="submit"
                        variant="primary"
                        class="w-full py-3"
                    >
                        Sign Up
                    </x-ui::button>
                </div>
            </div>
        </form>

        <div class="mt-6 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Already have an account?
                <a href="{{ route('login') }}" class="text-brand-500 hover:text-brand-600 font-semibold">
                    Sign In
                </a>
            </p>
        </div>
    </div>
</x-ui::card>
@endsection
