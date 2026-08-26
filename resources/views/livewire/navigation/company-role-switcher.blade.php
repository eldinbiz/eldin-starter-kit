<div class="flex items-center gap-2" x-data @click.outside="$wire.closeDropdowns()">
    {{-- 1. Company Selector Dropdown --}}
    @if($this->userCompanies->count() > 0)
        <div class="relative">
            <button
                type="button"
                wire:click="toggleCompanyDropdown"
                class="flex h-10 items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-hidden"
            >
                <svg class="fill-current text-brand-500" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M2 6C2 4.34315 3.34315 3 5 3H19C20.6569 3 22 4.34315 22 6V18C22 19.6569 20.6569 21 19 21H5C3.34315 21 2 19.6569 2 18V6ZM5 4.5C4.17157 4.5 3.5 5.17157 3.5 6V7.5H20.5V6C20.5 5.17157 19.8284 4.5 19 4.5H5ZM3.5 9V18C3.5 18.8284 4.17157 19.5 5 19.5H19C19.8284 19.5 20.5 18.8284 20.5 18V9H3.5Z" />
                </svg>
                <span class="max-w-[120px] truncate">
                    <strong class="text-gray-800 dark:text-white">{{ $this->activeCompany?->name ?? 'Select Company' }}</strong>
                </span>
                <svg class="fill-current text-gray-500" width="10" height="6" viewBox="0 0 12 8" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M1.41 0.589966L6 5.17997L10.59 0.589966L12 1.99997L6 7.99997L0 1.99997L1.41 0.589966Z" />
                </svg>
            </button>

            @if($companyDropdownOpen)
                <div
                    class="absolute right-0 mt-2 flex w-[220px] flex-col rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300 z-9999"
                >
                    <div class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        Switch Company
                    </div>
                    @foreach($this->userCompanies as $company)
                        @php $isActive = ($this->activeCompany?->id === $company->id); @endphp
                        <button
                            type="button"
                            wire:key="company-{{ $company->id }}"
                            wire:click="switchCompany({{ $company->id }})"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5 transition-colors {{ $isActive ? 'text-brand-500 font-semibold bg-brand-50/50 dark:bg-brand-500/10' : '' }}"
                        >
                            <span class="truncate">{{ $company->name }}</span>
                            @if($isActive)
                                <svg class="h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- 2. Role Selector Dropdown --}}
    @if($this->activeCompany && $this->availableRoles->count() > 0)
        <div class="relative">
            <button
                type="button"
                wire:click="toggleRoleDropdown"
                class="flex h-10 items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-hidden"
            >
                <span>Role: <strong class="text-gray-800 dark:text-white">{{ ucfirst($this->activeRole?->name ?? 'user') }}</strong></span>
                <svg class="fill-current text-gray-500" width="10" height="6" viewBox="0 0 12 8" fill="none">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M1.41 0.589966L6 5.17997L10.59 0.589966L12 1.99997L6 7.99997L0 1.99997L1.41 0.589966Z" />
                </svg>
            </button>

            @if($roleDropdownOpen)
                <div
                    class="absolute right-0 mt-2 flex w-[180px] flex-col rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300 z-9999"
                >
                    <div class="px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                        Switch Role
                    </div>
                    @foreach($this->availableRoles as $role)
                        @php $isActive = (($this->activeRole?->name ?? 'user') === $role->name); @endphp
                        <button
                            type="button"
                            wire:key="role-{{ $role->id ?? $role->name }}"
                            wire:click="switchRole('{{ $role->name }}')"
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5 transition-colors {{ $isActive ? 'text-brand-500 font-semibold bg-brand-50/50 dark:bg-brand-500/10' : '' }}"
                        >
                            <span>{{ ucfirst($role->name) }}</span>
                            @if($isActive)
                                <svg class="h-4 w-4 shrink-0 text-brand-500" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>
    @endif
</div>
