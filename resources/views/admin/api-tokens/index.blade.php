@extends('layouts.app')

@section('title', 'API Tokens')

@section('content')
<div x-data="{ pageName: 'API Tokens Governance' }">
    @include('partials.breadcrumb')
</div>

<div x-data="{ 
    showCreateModal: false,
    selectAll: true,
    allAbilities: {{ json_encode(array_column($endpoints, 'ability')) }},
    selectedAbilities: [],
    init() {
        this.selectedAbilities = [...this.allAbilities];
    },
    toggleSelectAll() {
        if (this.selectAll) {
            this.selectedAbilities = [...this.allAbilities];
        } else {
            this.selectedAbilities = [];
        }
    },
    updateSelectAll() {
        this.selectAll = this.selectedAbilities.length === this.allAbilities.length;
    }
}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    <div class="flex flex-col gap-3 mb-6 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">
                System API Tokens
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Manage access tokens, endpoint permissions, and lifecycles for service accounts and integrations.
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
                    class="ml-auto flex-shrink-0 text-sm text-brand-600 hover:text-brand-700 dark:text-brand-400 font-medium"
                >
                    Copy
                </button>
            </div>
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap text-left text-sm text-gray-500 dark:text-gray-400">
            <thead class="bg-gray-50 text-gray-700 dark:bg-white/[0.02] dark:text-gray-300 uppercase text-xs">
                <tr>
                    <th scope="col" class="px-6 py-4 font-semibold">Service Account</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Token Name</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Scopes / Permissions</th>
                    <th scope="col" class="px-6 py-4 font-semibold">Expires</th>
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
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-white/90">
                            {{ $token->name }}
                        </td>
                        <td class="px-6 py-4">
                            @if(empty($token->abilities) || in_array('*', $token->abilities))
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                    Full Access (*)
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300" title="{{ implode(', ', $token->abilities) }}">
                                    {{ count($token->abilities) }} Endpoints
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($token->expires_at)
                                @if($token->expires_at->isPast())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                        Expired
                                    </span>
                                @elseif($token->expires_at->diffInDays(now()) <= 7)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400" title="{{ $token->expires_at->format('Y-m-d H:i:s') }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        In {{ $token->expires_at->diffForHumans(['parts' => 1]) }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" title="{{ $token->expires_at->format('Y-m-d H:i:s') }}">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                        In {{ $token->expires_at->diffForHumans(['parts' => 1]) }}
                                    </span>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                    Never
                                </span>
                            @endif
                        </td>
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
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
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
            class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-800 max-h-[90vh] flex flex-col"
        >
            <div class="flex items-center justify-between mb-4 flex-shrink-0">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Generate New API Token</h3>
                    <p class="text-xs text-gray-500">Configure token identifier, lifecycle, and endpoint permissions.</p>
                </div>
                <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-500">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <form action="{{ route('admin.api-tokens.store') }}" method="POST" class="flex flex-col flex-1 overflow-hidden">
                @csrf
                <div class="overflow-y-auto pr-1 space-y-4 flex-1">
                    <!-- Token Name -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                            Token / Integration Name
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            required
                            placeholder="e.g., Zapier Approvals Sync, Billing Webhook"
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>

                    <!-- Expiration Selector -->
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-800 dark:text-white/90">
                            Token Expiration
                        </label>
                        <select 
                            name="expiry" 
                            class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 outline-hidden focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        >
                            <option value="30">30 Days</option>
                            <option value="60">60 Days</option>
                            <option value="90" selected>90 Days (Recommended)</option>
                            <option value="365">1 Year</option>
                            <option value="never">Never (No Expiration)</option>
                        </select>
                    </div>

                    <!-- Endpoint Permissions -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-800 dark:text-white/90">
                                Endpoint Permissions
                            </label>
                            <label class="inline-flex items-center gap-2 cursor-pointer text-xs text-brand-600 dark:text-brand-400 font-medium">
                                <input 
                                    type="checkbox" 
                                    x-model="selectAll" 
                                    @change="toggleSelectAll()"
                                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                Select All / Full Access (*)
                            </label>
                        </div>

                        <div class="space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/50 max-h-56 overflow-y-auto">
                            @foreach($endpoints as $ep)
                                <div class="flex items-center justify-between p-2 rounded hover:bg-white dark:hover:bg-gray-800 transition">
                                    <label class="flex items-center gap-3 cursor-pointer flex-1 min-w-0">
                                        <input 
                                            type="checkbox" 
                                            name="abilities[]" 
                                            value="{{ $ep['ability'] }}"
                                            x-model="selectedAbilities"
                                            @change="updateSelectAll()"
                                            class="rounded border-gray-300 text-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-700"
                                        />
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300">
                                                {{ $ep['method'] }}
                                            </span>
                                            <code class="text-xs font-mono font-medium text-gray-800 dark:text-gray-200 truncate">
                                                {{ $ep['path'] }}
                                            </code>
                                            <span class="text-xs text-gray-400 hidden sm:inline truncate">
                                                — {{ $ep['label'] }}
                                            </span>
                                        </div>
                                    </label>
                                    <a 
                                        href="{{ url('/docs/api') }}#/operations/{{ $ep['doc_anchor'] ?? '' }}" 
                                        target="_blank" 
                                        class="ml-2 text-xs text-brand-600 hover:text-brand-700 dark:text-brand-400 hover:underline flex-shrink-0 inline-flex items-center gap-1"
                                        title="View OpenAPI documentation reference for this endpoint"
                                    >
                                        Docs
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                        </svg>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex-shrink-0">
                    <button 
                        type="button" 
                        @click="showCreateModal = false"
                        class="rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    >
                        Cancel
                    </button>
                    <button 
                        type="submit"
                        class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600 shadow-theme-xs font-medium"
                    >
                        Generate Token
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
