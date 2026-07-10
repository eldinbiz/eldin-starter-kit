<aside
    x-data="{ sidebarHover: false }"
    @mouseenter="sidebarHover = true"
    @mouseleave="sidebarHover = false"
    :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
    class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0 duration-300 ease-linear"
>
    <!-- SIDEBAR HEADER -->
    <div
        :class="sidebarToggle ? (sidebarHover ? 'justify-between' : 'justify-center') : 'justify-between'"
        class="flex items-center gap-2 pt-8 pb-7 sidebar-header"
    >
        <a href="{{ url('/') }}" class="flex items-center gap-3">
            <span class="logo flex items-center gap-2 font-bold text-xl text-gray-800 dark:text-white" x-show="!sidebarToggle || sidebarHover" x-cloak>
                <!-- Mini SVG logo -->
                <svg width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="32" height="32" rx="8" fill="#465FFF"/>
                    <path d="M9 16L13.5 20.5L23 11" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span>TailAdmin</span>
            </span>
            <svg class="logo-icon" x-show="sidebarToggle && !sidebarHover" x-cloak width="32" height="32" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="32" height="32" rx="8" fill="#465FFF"/>
                <path d="M9 16L13.5 20.5L23 11" stroke="white" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>

        <!-- Hamburger toggle button (visible on mobile only) -->
        <button
            @click.stop="sidebarToggle = !sidebarToggle"
            class="block lg:hidden text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-white"
        >
            <svg class="fill-current" width="20" height="18" viewBox="0 0 20 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M19 8.175H2.98748L9.36248 1.6875C9.69998 1.35 9.69998 0.825 9.36248 0.4875C9.02498 0.15 8.49998 0.15 8.16248 0.4875L0.399976 8.3625C0.0624756 8.7 0.0624756 9.225 0.399976 9.5625L8.16248 17.4375C8.31248 17.5875 8.53748 17.6875 8.76248 17.6875C8.98748 17.6875 9.17498 17.6125 9.36248 17.4375C9.69998 17.1 9.69998 16.575 9.36248 16.2375L2.98748 9.75H19C19.45 9.75 19.825 9.375 19.825 8.925C19.825 8.475 19.45 8.175 19 8.175Z" />
            </svg>
        </button>
    </div>
    <!-- SIDEBAR HEADER -->

    <div class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar flex-1">
        <!-- Sidebar Menu -->
        <nav x-data="{ selectedGroup: 'Menu', selectedItem: 'Dashboard' }">
            <!-- Group 1: MENU -->
            <div class="mb-6">
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span x-show="!sidebarToggle || sidebarHover" x-cloak>
                        MENU
                    </span>
                    <span x-show="sidebarToggle && !sidebarHover" x-cloak class="text-center text-[10px]">MENU</span>
                </h3>

                <ul class="flex flex-col gap-1.5">
                    <li>
                        <a
                            href="{{ url('/') }}"
                            class="menu-item group {{ Request::is('/') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <!-- Dashboard Icon -->
                            <svg
                                class="{{ Request::is('/') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}"
                                width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                            >
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z" />
                            </svg>
                            <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>
                                Dashboard
                            </span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Group 2: SYSTEM ADMINISTRATION -->
            <div>
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span x-show="!sidebarToggle || sidebarHover" x-cloak>
                        SYSTEM ADMINISTRATION
                    </span>
                    <span x-show="sidebarToggle && !sidebarHover" x-cloak class="text-center text-[10px]">SYS</span>
                </h3>

                <ul class="flex flex-col gap-1.5">

                    <!-- User Management -->
                    @if (session('active_role') === 'admin')
                    <li>
                        <a
                            href="{{ route('admin.users.index') }}"
                            class="menu-item group {{ Request::is('admin/users*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Users Icon -->
                                <svg class="fill-current {{ Request::is('admin/users*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2ZM8.5 7C8.5 5.067 10.067 3.5 12 3.5C13.933 3.5 15.5 5.067 15.5 7C15.5 8.933 13.933 10.5 12 10.5C10.067 10.5 8.5 8.933 8.5 7ZM12 13.5C7.30558 13.5 3.5 17.3056 3.5 22C3.5 22.4142 3.83579 22.75 4.25 22.75H19.75C20.1642 22.75 20.5 22.4142 20.5 22C20.5 17.3056 16.6944 13.5 12 13.5ZM5.03 21.25C5.46 17.81 8.41 15 12 15C15.59 15 18.54 17.81 18.97 21.25H5.03Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>User Management</span>
                            </span>
                        </a>
                    </li>
                    @endif

                    <!-- Org Structure (Placeholder) -->
                    <li>
                        <a
                            href="#"
                            onclick="return false;"
                            class="menu-item group menu-item-inactive opacity-60 cursor-not-allowed flex items-center justify-between"
                            title="Coming Soon"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Structure Icon -->
                                <svg class="fill-current text-gray-500" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11 2C9.34315 2 8 3.34315 8 5V7H6C4.89543 7 4 7.89543 4 9V12C4 13.1046 4.89543 14 6 14H8V16C8 17.6569 9.34315 19 11 19H13C14.6569 19 16 17.6569 16 16V14H18C19.1046 14 20 13.1046 20 12V9C20 7.89543 19.1046 7 18 7H16V5C16 3.34315 14.6569 2 13 2H11ZM9.5 5C9.5 4.17157 10.1716 3.5 11 3.5H13C13.8284 3.5 14.5 4.17157 14.5 5V7H9.5V5ZM5.5 9C5.5 8.72386 5.72386 8.5 6 8.5H18C18.2761 8.5 18.5 8.72386 18.5 9V12C18.5 12.2761 18.2761 12.5 18 12.5H6C5.72386 12.5 5.5 12.2761 5.5 12V9ZM11 17.5C10.1716 17.5 9.5 16.8284 9.5 16V14H14.5V16C14.5 16.8284 13.8284 17.5 13 17.5H11Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Org Structure</span>
                            </span>
                            <span class="text-[9px] bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded" x-show="!sidebarToggle || sidebarHover" x-cloak>Lock</span>
                        </a>
                    </li>

                    <!-- System Variables (Placeholder) -->
                    <li>
                        <a
                            href="#"
                            onclick="return false;"
                            class="menu-item group menu-item-inactive opacity-60 cursor-not-allowed flex items-center justify-between"
                            title="Coming Soon"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Variables/Settings Icon -->
                                <svg class="fill-current text-gray-500" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.5 4C3.5 3.44772 3.94772 3 4.5 3H19.5C20.0523 3 20.5 3.44772 20.5 4V10C20.5 10.5523 20.0523 11 19.5 11H4.5C3.94772 11 3.5 10.5523 3.5 10V4ZM5 4.5V9.5H19V4.5H5ZM3.5 14C3.5 13.4477 3.94772 13 4.5 13H19.5C20.0523 13 20.5 13.4477 20.5 14V20C20.5 20.5523 20.0523 21 19.5 21H4.5C3.94772 21 3.5 20.5523 3.5 20V14ZM5 14.5V19.5H19V14.5H5Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>System Variables</span>
                            </span>
                            <span class="text-[9px] bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded" x-show="!sidebarToggle || sidebarHover" x-cloak>Lock</span>
                        </a>
                    </li>

                    <!-- SMTP Settings (Placeholder) -->
                    <li>
                        <a
                            href="#"
                            onclick="return false;"
                            class="menu-item group menu-item-inactive opacity-60 cursor-not-allowed flex items-center justify-between"
                            title="Coming Soon"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Mail Icon -->
                                <svg class="fill-current text-gray-500" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M1.75 5C1.75 3.75736 2.75736 2.75 4 2.75H20C21.2426 2.75 22.25 3.75736 22.25 5V19C22.25 20.2426 21.2426 21.25 20 21.25H4C2.75736 21.25 1.75 20.2426 1.75 19V5ZM4 4.25H20C20.4142 4.25 20.75 4.58579 20.75 5V7.89304L12.548 12.9238C12.2125 13.1296 11.7875 13.1296 11.452 12.9238L3.25 7.89304V5C3.25 4.58579 3.58579 4.25 4 4.25ZM3.25 9.6384L10.682 14.1951C11.4883 14.6896 12.5117 14.6896 13.318 14.1951L20.75 9.6384V19C20.75 19.4142 20.4142 19.75 20 19.75H4C3.58579 19.75 3.25 19.4142 3.25 19V9.6384Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>SMTP Settings</span>
                            </span>
                            <span class="text-[9px] bg-gray-200 dark:bg-gray-800 text-gray-600 dark:text-gray-400 px-1.5 py-0.5 rounded" x-show="!sidebarToggle || sidebarHover" x-cloak>Lock</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</aside>
