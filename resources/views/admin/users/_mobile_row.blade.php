<div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03]">
    {{-- User Info --}}
    <div class="flex items-center gap-3 mb-3">
        <span class="h-10 w-10 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400 shrink-0">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </span>
        <div class="min-w-0">
            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate">{{ $user->name }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</p>
        </div>
    </div>

    {{-- Roles & Companies --}}
    <div class="flex flex-wrap items-center gap-1.5 mb-3">
        @if(isset($userCompanyRoleMap[$user->id]) && $userCompanyRoleMap[$user->id]->isNotEmpty())
            @foreach($userCompanyRoleMap[$user->id] as $companyData)
                @foreach($companyData['roles'] as $role)
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $roleColorClasses($role->name) }}">
                        {{ match($role->name) {
                            'sysadmin' => 'SysAdmin',
                            'company-admin' => 'Company Admin',
                            'platform-admin' => 'Admin',
                            default => ucfirst(str_replace('-', ' ', $role->name))
                        } }}
                    </span>
                @endforeach
                @if($companyData['company_name'])
                    <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                        {{ $companyData['company_name'] }}
                    </span>
                @endif
            @endforeach
        @else
            <span class="text-xs text-gray-400 dark:text-gray-500">&mdash;</span>
        @endif
    </div>

    {{-- Footer: Joined Date + Actions --}}
    <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800">
        <span class="text-xs text-gray-500 dark:text-gray-400">Joined @formatDate($user->created_at)</span>
        <div class="flex items-center gap-2">
            @if(($canImpersonate ?? false) && $user->id !== ($authUserId ?? null))
                <a
                    href="{{ route('impersonate', $user->id) }}"
                    title="Impersonate {{ $user->name }}"
                    class="text-gray-500 hover:text-amber-500 dark:text-gray-400 dark:hover:text-amber-400"
                >
                    <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24">
                        <path d="M12 2a5 5 0 0 0-5 5v3H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-1V7a5 5 0 0 0-5-5zm-3 5a3 3 0 0 1 6 0v3H9V7zm3 7a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"/>
                    </svg>
                </a>
            @endif

            <a
                href="{{ route('admin.users.edit', $user->id) }}"
                class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
            >
                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                </svg>
            </a>
            @if($user->id !== ($authUserId ?? Auth::id()))
                <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    @csrf
                    @method('DELETE')
                    <button
                        type="submit"
                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                    >
                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
