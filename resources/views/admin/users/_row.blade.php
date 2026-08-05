<tr>
    <td class="py-3.5">
        <div class="flex items-center gap-3">
            <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">
                {{ strtoupper(substr($user->name, 0, 2)) }}
            </span>
            <div>
                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $user->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            </div>
        </div>
    </td>
    <td class="py-3.5">
        @if(isset($userCompanyRoleMap[$user->id]) && $userCompanyRoleMap[$user->id]->isNotEmpty())
            @foreach($userCompanyRoleMap[$user->id] as $companyData)
                <div class="{{ !$loop->first ? 'mt-2' : '' }}">
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($companyData['roles'] as $role)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium {{ $roleColorClasses($role->name) }}">
                                {{ ucfirst($role->name === 'platform-admin' ? 'Admin' : $role->name) }}
                            </span>
                        @endforeach
                        @if($companyData['company_name'])
                            <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-600 dark:bg-purple-500/15 dark:text-purple-400">
                                {{ $companyData['company_name'] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <span class="text-gray-400 text-theme-sm dark:text-gray-500">&mdash;</span>
        @endif
    </td>
    <td class="py-3.5">
        <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $user->created_at->format('M d, Y') }}</p>
    </td>
    <td class="py-3.5 text-right">
        <div class="flex items-center justify-end gap-2">
            @canImpersonate
                @canBeImpersonated($user)
                    @if($user->id !== Auth::id())
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
                @endCanBeImpersonated
            @endCanImpersonate

            <a
                href="{{ route('admin.users.edit', $user->id) }}"
                class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
            >
                <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                    <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                </svg>
            </a>

            @if($user->id !== Auth::id())
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
    </td>
</tr>
