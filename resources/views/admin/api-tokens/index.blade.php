@extends('layouts.app')

@section('title', 'API Tokens')

@section('content')
<div x-data="{ pageName: 'API Tokens Governance' }">
    @include('partials.breadcrumb')
</div>

<div x-data="{ showCreateModal: false }" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                System API Tokens
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage access tokens for service accounts and external integrations.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a
                href="{{ url('/docs/api') }}"
                target="_blank"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 focus:outline-hidden"
            >
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                View API Docs
            </a>
            <button
                @click="showCreateModal = true"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M10 4.16666V15.8333M4.16663 10H15.8333" stroke="currentColor" stroke-width="1.66667" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Generate Token
            </button>
        </div>
    </div>

    @if(session('flash') && isset(session('flash')['api_token']))
        <div class="mb-6 rounded-lg bg-green-50 p-4 border border-green-200 dark:bg-green-900/20 dark:border-green-800">
            <h4 class="text-sm font-semibold text-green-800 dark:text-green-400 mb-2">Token Generated Successfully!</h4>
            <p class="text-sm text-green-700 dark:text-green-300 mb-4">
                Please copy this token now. For your security, it will not be shown again.
            </p>
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-3 rounded border border-green-200 dark:border-green-700">
                <code class="text-sm break-all font-mono text-gray-800 dark:text-gray-200" id="tokenText">{{ session('flash')['api_token'] }}</code>
                <button 
                    onclick="navigator.clipboard.writeText(document.getElementById('tokenText').innerText); alert('Copied to clipboard!')"
                    class="ml-auto flex-shrink-0 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400"
                >
                    Copy
                </button>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-gray-700 dark:bg-white/[0.02] dark:text-gray-300 uppercase">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Service Account</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Token Name</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Last Used</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Created</th>
                    <th scope="col" class="px-6 py-4 text-right font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/[0.05]">
                @forelse($systemUsers as $user)
                    @foreach($user->tokens as $token)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800 dark:text-white/90">{{ $user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $user->email }}</div>
                        </td>
                        <td class="px-6 py-4">{{ $token->name }}</td>
                        <td class="px-6 py-4">
                            {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
                        </td>
                        <td class="px-6 py-4">{{ $token->created_at->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-right">
                            <form action="{{ route('admin.api-tokens.destroy', $token->id) }}" method="POST" class="inline-block"
                                onsubmit="return confirm('Are you sure you want to revoke this token? Any integrations using it will immediately lose access.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-error-500 hover:text-error-600 font-medium">
                                    Revoke
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No API tokens have been generated yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <div 
        x-show="showCreateModal" 
        x-cloak 
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-sm"
    >
        <div 
            @click.away="showCreateModal = false"
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800"
        >
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Generate New Token</h3>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form action="{{ route('admin.api-tokens.store') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="mb-2.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                        Token / Integration Name
                    </label>
                    <input 
                        type="text" 
                        name="name" 
                        required
                        placeholder="e.g., Zapier Integration, Mobile App"
                        class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-gray-800 outline-hidden focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    />
                    <p class="mt-2 text-xs text-gray-500">
                        This will automatically create a dedicated System Service Account for this integration.
                    </p>
                </div>
                
                <div class="flex justify-end gap-3 mt-6">
                    <button 
                        type="button" 
                        @click="showCreateModal = false"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600"
                    >
                        Generate Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
