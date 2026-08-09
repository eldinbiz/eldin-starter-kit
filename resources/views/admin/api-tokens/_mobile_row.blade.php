<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    {{-- Service Account Info & Token Name --}}
    <div class="flex items-center justify-between gap-3 mb-3">
        <div class="flex items-center gap-3 min-w-0">
            <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </span>
            <div class="min-w-0">
                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ $token->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user?->name ?? 'System Bot' }} ({{ $user?->email }})</p>
            </div>
        </div>

        {{-- Expiry Status Badge --}}
        <div>
            @if($token->expires_at)
                @if($token->expires_at->isPast())
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                        Expired
                    </span>
                @elseif($token->expires_at->diffInDays(now()) <= 7)
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400" title="{{ $token->expires_at->format('Y-m-d H:i:s') }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                        In {{ $token->expires_at->diffForHumans(['parts' => 1]) }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" title="{{ $token->expires_at->format('Y-m-d H:i:s') }}">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        In {{ $token->expires_at->diffForHumans(['parts' => 1]) }}
                    </span>
                @endif
            @else
                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    Never
                </span>
            @endif
        </div>
    </div>

    {{-- Scopes Badge --}}
    <div class="flex items-center gap-2 mb-3">
        @if(empty($token->abilities) || in_array('*', $token->abilities))
            <button 
                type="button"
                @click="openScopesModal('{{ addslashes($token->name) }}', '{{ addslashes($user?->name ?? 'System Bot') }}', '{{ addslashes($user?->email ?? '') }}', {{ json_encode($token->abilities ?? ['*']) }})"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 hover:bg-blue-200 dark:bg-blue-900/30 dark:text-blue-400 cursor-pointer"
            >
                <span>Full Access (*)</span>
                <svg class="w-3 h-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        @else
            <button 
                type="button"
                @click="openScopesModal('{{ addslashes($token->name) }}', '{{ addslashes($user?->name ?? 'System Bot') }}', '{{ addslashes($user?->email ?? '') }}', {{ json_encode($token->abilities) }})"
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 cursor-pointer"
            >
                <span>{{ count($token->abilities) }} Endpoints</span>
                <svg class="w-3 h-3 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </button>
        @endif
    </div>

    {{-- Footer: Last Used + Actions --}}
    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">
            Last used: {{ $token->last_used_at ? $token->last_used_at->diffForHumans() : 'Never' }}
        </span>
        <form action="{{ route('admin.api-tokens.destroy', $token->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to revoke this API token? Any integrations using it will immediately stop working.');">
            @csrf
            @method('DELETE')
            <button 
                type="submit" 
                class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium hover:underline cursor-pointer"
            >
                Revoke
            </button>
        </form>
    </div>
</div>
