<header
    x-data="{ menuToggle: false, userDropdown: false, notificationDropdown: false, messageDropdown: false }"
    class="sticky top-0 z-99 flex w-full border-b border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
>
    <div class="flex grow flex-col justify-between lg:flex-row lg:px-6 w-full">
        <div class="flex w-full items-center justify-between gap-2 border-b border-gray-200 px-4 py-3 lg:justify-normal lg:border-b-0 lg:px-0 lg:py-4 dark:border-gray-800">

            <!-- Hamburger Menu Toggle (Desktop & Mobile) -->
            <button
                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800"
                @click.stop="sidebarToggle = !sidebarToggle"
            >
                <!-- Hamburger Icon -->
                <svg class="fill-current" width="16" height="12" viewBox="0 0 16 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M0.583252 1C0.583252 0.585788 0.919038 0.25 1.33325 0.25H14.6666C15.0808 0.25 15.4166 0.585786 15.4166 1C15.4166 1.41421 15.0808 1.75 14.6666 1.75L1.33325 1.75C0.919038 1.75 0.583252 1.41422 0.583252 1ZM0.583252 11C0.583252 10.5858 0.919038 10.25 1.33325 10.25L14.6666 10.25C15.0808 10.25 15.4166 10.5858 15.4166 11C15.4166 11.4142 15.0808 11.75 14.6666 11.75L1.33325 11.75C0.919038 11.75 0.583252 11.4142 0.583252 11ZM1.33325 5.25C0.919038 5.25 0.583252 5.58579 0.583252 6C0.583252 6.41421 0.919038 6.75 1.33325 6.75L7.99992 6.75C8.41413 6.75 8.74992 6.41421 8.74992 6C8.74992 5.58579 8.41413 5.25 7.99992 5.25L1.33325 5.25Z" />
                </svg>
            </button>

            <!-- Search Bar -->
            <div class="hidden sm:block ml-4 flex-1 max-w-xs">
                <form action="#" method="GET">
                    <div class="relative">
                        <span class="absolute top-1/2 left-3 -translate-y-1/2 text-gray-400">
                            <svg class="fill-current" width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M3.04175 9.37363C3.04175 5.87693 5.87711 3.04199 9.37508 3.04199C12.8731 3.04199 15.7084 5.87693 15.7084 9.37363C15.7084 12.8703 12.8731 15.7053 9.37508 15.7053C5.87711 15.7053 3.04175 12.8703 3.04175 9.37363ZM9.37508 1.54199C5.04902 1.54199 1.54175 5.04817 1.54175 9.37363C1.54175 13.6991 5.04902 17.2053 9.37508 17.2053C11.2674 17.2053 13.003 16.5344 14.357 15.4176L17.177 18.238C17.4699 18.5309 17.9448 18.5309 18.2377 18.238C18.5306 17.9451 18.5306 17.4703 18.2377 17.1774L15.418 14.3573C16.5365 13.0033 17.2084 11.2669 17.2084 9.37363C17.2084 5.04817 13.7011 1.54199 9.37508 1.54199Z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            placeholder="Type to search..."
                            class="w-full rounded-lg border border-gray-200 bg-gray-50 py-2.5 pl-10 pr-4 text-sm outline-hidden focus:border-brand-500 focus:bg-white dark:border-gray-800 dark:bg-gray-950 dark:text-white dark:focus:border-brand-500"
                        />
                    </div>
                </form>
            </div>

            <!-- Toolbar / Profile Area -->
            <div class="flex items-center gap-3.5 ml-auto">
                <!-- 1. Dark Mode Switcher -->
                <button
                    class="relative flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800"
                    @click="darkMode = !darkMode"
                >
                    <!-- Sun Icon (shows in dark mode) -->
                    <svg x-show="darkMode" x-cloak class="fill-current text-warning-500" width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zm0 8c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3zm0-9c.28 0 .5-.22.5-.5V4c0-.28-.22-.5-.5-.5s-.5.22-.5.5v1.5c0 .28.22.5.5.5zm0 12c.28 0 .5-.22.5-.5V20c0-.28-.22-.5-.5-.5s-.5.22-.5.5v1.5c0 .28.22.5.5.5zm8.5-6.5h-1.5c-.28 0-.5.22-.5.5s.22.5.5.5h1.5c.28 0 .5-.22.5-.5s-.22-.5-.5-.5zM6 12c0-.28-.22-.5-.5-.5H4c-.28 0-.5.22-.5.5s.22.5.5.5h1.5c.28 0 .5-.22.5-.5zm11.24-5.36c.2-.2.2-.51 0-.71l-1.06-1.06c-.2-.2-.51-.2-.71 0s-.2.51 0 .71l1.06 1.06c.2.2.51.2.71 0zM7.83 16.17c.2-.2.2-.51 0-.71l-1.06-1.06c-.2-.2-.51-.2-.71 0s-.2.51 0 .71l1.06 1.06c.2.2.51.2.71 0zm8.7-1.06c-.2-.2-.51-.2-.71 0l-1.06 1.06c-.2.2-.2.51 0 .71s.51.2.71 0l1.06-1.06c.2-.2.2-.51 0-.71zM7.12 5.12c-.2-.2-.51-.2-.71 0L5.36 6.18c-.2.2-.2.51 0 .71s.51.2.71 0l1.06-1.06c.2-.2.2-.51 0-.71z"/>
                    </svg>
                    <!-- Moon Icon (shows in light mode) -->
                    <svg x-show="!darkMode" class="fill-current text-gray-500" width="22" height="22" viewBox="0 0 24 24" fill="none">
                        <path d="M12 3c-4.97 0-9 4.03-9 9s4.03 9 9 9 9-4.03 9-9c0-.46-.04-.92-.1-1.36-.98 1.37-2.58 2.26-4.4 2.26-2.98 0-5.4-2.42-5.4-5.4 0-1.81.89-3.42 2.26-4.4-.44-.06-.9-.1-1.36-.1z" />
                    </svg>
                </button>

                <!-- Company Selector Dropdown -->
                @auth
                @php $activeCompany = Auth::user()->getActiveCompany(); @endphp
                @if(Auth::user()->companies->count() > 0)
                <div class="relative" x-data="{ companyDropdown: false }" @click.outside="companyDropdown = false">
                    <button
                        @click="companyDropdown = !companyDropdown"
                        class="flex h-10 items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-hidden"
                    >
                        <svg class="fill-current text-brand-500" width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M2 6C2 4.34315 3.34315 3 5 3H19C20.6569 3 22 4.34315 22 6V18C22 19.6569 20.6569 21 19 21H5C3.34315 21 2 19.6569 2 18V6ZM5 4.5C4.17157 4.5 3.5 5.17157 3.5 6V7.5H20.5V6C20.5 5.17157 19.8284 4.5 19 4.5H5ZM3.5 9V18C3.5 18.8284 4.17157 19.5 5 19.5H19C19.8284 19.5 20.5 18.8284 20.5 18V9H3.5Z" />
                        </svg>
                        <span class="max-w-[120px] truncate"><strong class="text-gray-800 dark:text-white">{{ $activeCompany?->name ?? 'Select Company' }}</strong></span>
                        <svg class="fill-current text-gray-500" width="10" height="6" viewBox="0 0 12 8" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.41 0.589966L6 5.17997L10.59 0.589966L12 1.99997L6 7.99997L0 1.99997L1.41 0.589966Z" />
                        </svg>
                    </button>
                    <div
                        x-show="companyDropdown"
                        x-cloak
                        class="absolute right-0 mt-2 flex w-[220px] flex-col rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300 z-9999"
                    >
                        @foreach(Auth::user()->companies as $company)
                            <form action="{{ route('switch-company') }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $company->id }}">
                                <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5 {{ ($activeCompany?->id === $company->id) ? 'text-brand-500 font-semibold' : '' }}">
                                    {{ $company->name }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
                @endif
                @endauth

                <!-- Role Selector Dropdown -->
                @auth
                <div class="relative" x-data="{ roleDropdown: false }" @click.outside="roleDropdown = false">
                    <button
                        @click="roleDropdown = !roleDropdown"
                        class="flex h-10 items-center justify-between gap-2 rounded-lg border border-gray-200 px-3 text-sm text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 focus:outline-hidden"
                    >
                        <span>Role: <strong class="text-gray-800 dark:text-white">{{ ucfirst(Auth::user()->getActiveRole()?->name ?? 'user') }}</strong></span>
                        <svg class="fill-current text-gray-500" width="10" height="6" viewBox="0 0 12 8" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.41 0.589966L6 5.17997L10.59 0.589966L12 1.99997L6 7.99997L0 1.99997L1.41 0.589966Z" />
                        </svg>
                    </button>
                    <!-- Dropdown Panel -->
                    <div
                        x-show="roleDropdown"
                        x-cloak
                        class="absolute right-0 mt-2 flex w-[160px] flex-col rounded-xl border border-gray-200 bg-white p-2 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300 z-9999"
                    >
                        @php $activeCompany = Auth::user()->getActiveCompany(); @endphp
                        @foreach(Auth::user()->getCompanyRoles($activeCompany) as $role)
                            <form action="{{ route('switch-role') }}" method="POST" class="w-full">
                                @csrf
                                <input type="hidden" name="role" value="{{ $role->name }}">
                                <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5 {{ (Auth::user()->getActiveRole()?->name ?? 'user') === $role->name ? 'text-brand-500 font-semibold' : '' }}">
                                    {{ ucfirst($role->name) }}
                                </button>
                            </form>
                        @endforeach
                    </div>
                </div>
                @endauth

                <!-- 2. User Profile Dropdown -->
                <div class="relative" @click.outside="userDropdown = false">
                    <button
                        @click="userDropdown = !userDropdown"
                        class="flex items-center gap-4 text-left"
                    >
                        <span class="hidden text-right lg:block">
                            <span class="block text-sm font-medium text-gray-800 dark:text-white">
                                {{ Auth::user()->name }}
                            </span>
                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">
                                {{ ucfirst(Auth::user()->getActiveRole()?->name ?? 'User') }}
                            </span>
                        </span>

                        <span class="h-10 w-10 rounded-full bg-brand-100 flex items-center justify-center text-brand-600 font-bold dark:bg-brand-500/10 dark:text-brand-400">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </span>

                        <!-- Arrow Down -->
                        <svg class="fill-current text-gray-500 hidden sm:block" width="12" height="8" viewBox="0 0 12 8" fill="none">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M1.41 0.589966L6 5.17997L10.59 0.589966L12 1.99997L6 7.99997L0 1.99997L1.41 0.589966Z" />
                        </svg>
                    </button>

                    <!-- Dropdown Panel -->
                    <div
                        x-show="userDropdown"
                        x-cloak
                        class="absolute right-0 mt-4 flex w-[200px] flex-col rounded-xl border border-gray-200 bg-white p-2.5 shadow-theme-lg dark:border-gray-800 dark:bg-gray-900 text-gray-700 dark:text-gray-300"
                    >
                        <div class="px-3.5 py-3 border-b border-gray-100 dark:border-gray-800">
                            <p class="text-sm font-semibold text-gray-800 dark:text-white truncate">
                                {{ Auth::user()->email }}
                            </p>
                        </div>
                        <ul class="flex flex-col gap-1.5 pt-2">
                            <li>
                                <a href="#" class="flex items-center gap-3.5 rounded-lg px-3.5 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5">
                                    My Profile
                                </a>
                            </li>
                            <li>
                                <a href="#" class="flex items-center gap-3.5 rounded-lg px-3.5 py-2 text-sm font-medium hover:bg-gray-100 dark:hover:bg-white/5">
                                    Account Settings
                                </a>
                            </li>
                        </ul>
                        <div class="mt-2.5 border-t border-gray-100 pt-2.5 dark:border-gray-800">
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-3.5 rounded-lg px-3.5 py-2 text-sm font-medium text-error-600 hover:bg-error-50 dark:text-error-400 dark:hover:bg-error-500/10">
                                    Log Out
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
