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

            <!-- Group 2: APPROVALS -->
            @canany(['view-approval-inbox', 'view-approval-history', 'view-approval-requests', 'manage-approvals', 'manage-approval-workflows'])
            <div class="mb-6">
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span x-show="!sidebarToggle || sidebarHover" x-cloak>
                        APPROVALS
                    </span>
                    <span x-show="sidebarToggle && !sidebarHover" x-cloak class="text-center text-[10px]">APPR</span>
                </h3>

                <ul class="flex flex-col gap-1.5">
                    <!-- Approval Inbox -->
                    @can('view-approval-inbox')
                    <li>
                        <a
                            href="{{ route('approvals.inbox') }}"
                            class="menu-item group {{ Request::is('approvals/inbox*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="fill-current {{ Request::is('approvals/inbox*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.25 2.25C3.17893 2.25 1.5 3.92893 1.5 6V11.25C1.5 13.3211 3.17893 15 5.25 15H8.25V19.5C8.25 20.7426 9.25736 21.75 10.5 21.75H18.75C20.8211 21.75 22.5 20.0711 22.5 18V12.75C22.5 10.6789 20.8211 9 18.75 9H15.75V6C15.75 3.92893 14.0711 2.25 12 2.25H5.25ZM14.25 10.5H18.75C19.9926 10.5 21 11.5074 21 12.75V18C21 19.2426 19.9926 20.25 18.75 20.25H10.5C9.25736 20.25 8.25 19.2426 8.25 18V13.5H14.25V10.5ZM14.25 9V6C14.25 4.75736 13.2426 3.75 12 3.75H5.25C4.00736 3.75 3 4.75736 3 6V11.25C3 12.4926 4.00736 13.5 5.25 13.5H12C13.2426 13.5 14.25 12.4926 14.25 11.25V9Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Inbox</span>
                            </span>
                        </a>
                    </li>
                    @endcan

                    <!-- Approval History -->
                    @can('view-approval-history')
                    <li>
                        <a
                            href="{{ route('approvals.history') }}"
                            class="menu-item group {{ Request::is('approvals/history*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="fill-current {{ Request::is('approvals/history*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>History</span>
                            </span>
                        </a>
                    </li>
                    @endcan

                    <!-- My Requests -->
                    @can('view-approval-requests')
                    <li>
                        <a
                            href="{{ route('approvals.requests') }}"
                            class="menu-item group {{ Request::is('approvals/requests*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="fill-current {{ Request::is('approvals/requests*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2.25C4.75736 2.25 3.75 3.25736 3.75 4.5V19.5C3.75 20.7426 4.75736 21.75 6 21.75H18C19.2426 21.75 20.25 20.7426 20.25 19.5V9C20.25 8.80109 20.171 8.61032 20.0303 8.46967L14.2803 2.71967C14.1397 2.57902 13.9489 2.5 13.75 2.5H13.75C13.5339 2.50001 13.3268 2.58624 13.1742 2.73966L8.23966 7.67417C8.0871 7.82673 8.00087 8.03387 8.00087 8.25C8.00087 8.46613 8.0871 8.67327 8.23966 8.82583L13.1742 13.7603C13.3268 13.9137 13.5339 14 13.75 14C13.9661 14 14.1733 13.9137 14.3258 13.7603L14.75 13.3361V12.25H18.5V19.5C18.5 19.7761 18.2761 20 18 20H6C5.72386 20 5.5 19.7761 5.5 19.5V4.5C5.5 4.22386 5.72386 4 6 4H13.25V7.5C13.25 8.32843 13.9216 9 14.75 9H18.5C18.9142 9 19.25 8.66421 19.25 8.25C19.25 7.83579 18.9142 7.5 18.5 7.5H14.75L14.75 3.5L18.1893 3.5L18.75 4.06066V9H14.75C13.9216 9 13.25 8.32843 13.25 7.5V4H13V4.06066L14.0607 5.12132C14.6454 5.70603 14.6464 6.65471 14.0617 7.23934L13.75 7.55101V8.25C13.75 8.66421 14.0858 9 14.5 9C14.9142 9 15.25 8.66421 15.25 8.25V7.55101L15.5614 7.23966C15.714 7.0871 15.8002 6.87996 15.8002 6.66383C15.8002 6.4477 15.714 6.24056 15.5614 6.08799L14.4393 4.96599C13.8378 4.36443 13.0217 4.0237 12.1554 4.00096C12.0584 4.00034 11.9614 4.00003 11.8644 4.00003L6 4H5.5V4.00003C5.22386 4.00003 5 4.22389 5 4.50003V19.5C5 19.7761 5.22386 20 5.5 20H18.5C18.7761 20 19 19.7761 19 19.5V12.25H14.75C14.3358 12.25 14 12.5858 14 13V13.3361L13.0617 14.2744C12.4769 14.8591 11.5244 14.8591 10.9397 14.2744L5.9545 9.28932L9.67417 5.56966C9.82673 5.4171 9.91296 5.20996 9.91296 4.99383C9.91296 4.7777 9.82673 4.57056 9.67417 4.41799L8.82583 3.56966C8.68263 3.42646 8.50915 3.34929 8.30109 3.35167C8.09907 3.35393 7.92668 3.43234 7.78739 3.57268L3.23739 8.12268C3.08724 8.27443 3.00087 8.48152 3.00087 8.69765C3.00087 8.91378 3.08724 9.12087 3.23739 9.27262L8.17203 14.2073C8.32476 14.3574 8.53185 14.4438 8.74798 14.4438C8.96411 14.4438 9.1712 14.3574 9.32295 14.2073L14.2576 9.27262C14.4078 9.12087 14.4941 8.91378 14.4941 8.69765C14.4941 8.48152 14.4078 8.27443 14.2576 8.12268L13.75 7.61508V6.19034L14.6893 7.12959C15.2754 7.71572 15.2735 8.66401 14.6873 9.25024L14.1893 9.74821V9V8.74821L13.75 8.30891L13.6893 8.36959C13.1061 8.95276 12.159 8.95358 11.5745 8.36959L10.5134 7.3085C10.4209 7.216 10.3156 7.13911 10.2016 7.0801L9.17417 6.05262L9.67417 5.55262C9.68564 5.54131 9.69702 5.52994 9.70831 5.5185C9.85799 5.36524 10.0657 5.27834 10.2824 5.27834C10.4991 5.27834 10.7068 5.36524 10.8566 5.5185L12.7025 7.36432C12.855 7.51688 13.0622 7.60312 13.2783 7.60312C13.4944 7.60312 13.7016 7.51688 13.8541 7.36432L14.7724 6.44605C14.925 6.29349 15.0112 6.08635 15.0112 5.87022C15.0112 5.65409 14.925 5.44695 14.7724 5.29439L13.8541 4.37612C13.6917 4.21371 13.4747 4.10569 13.2349 4.06593C13.0912 4.04188 12.9465 4.02315 12.8009 4.00981L12.1524 4.00325L13.1893 3.5L14.4393 4.46599C14.8369 4.86372 15.0688 5.38777 15.0915 5.93462C15.0966 6.01907 15.0992 6.10378 15.0992 6.18875L13.75 7.55101V6.69034L14.2576 6.1827C14.4108 6.02914 14.498 5.82154 14.498 5.605C14.498 5.38846 14.4108 5.18086 14.2576 5.0273L11.8768 2.64648C11.7154 2.48327 11.5034 2.37122 11.2656 2.32138C11.1125 2.28971 10.957 2.26819 10.8002 2.25097L10.1862 2.24498L10 2.5Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>My Requests</span>
                            </span>
                        </a>
                    </li>
                    @endcan

                    <!-- Manage Approvals -->
                    @can('manage-approvals')
                    <li>
                        <a
                            href="{{ route('admin.approval-management.index') }}"
                            class="menu-item group {{ Request::is('admin/approval-management*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="fill-current {{ Request::is('admin/approval-management*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M3.25 3C2.00736 3 1 4.00736 1 5.25V18.75C1 19.9926 2.00736 21 3.25 21H20.75C21.9926 21 23 19.9926 23 18.75V5.25C23 4.00736 21.9926 3 20.75 3H3.25ZM2.5 8.25V5.25C2.5 4.83579 2.83579 4.5 3.25 4.5H9V8.25H2.5ZM2.5 14.25V9.75H9V19.5H3.25C2.83579 19.5 2.5 19.1642 2.5 18.75V14.25ZM10.5 19.5V9.75H17V15.75H17.75V19.5H10.5ZM17 8.25V4.5H20.75C21.1642 4.5 21.5 4.83579 21.5 5.25V13.25H18.5V17V18.75C18.5 19.1642 18.1642 19.5 17.75 19.5H17.75V8.25H17Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Manage Approvals</span>
                            </span>
                        </a>
                    </li>
                    @endcan

                    <!-- Workflow Templates -->
                    @can('manage-approval-workflows')
                    <li>
                        <a
                            href="{{ route('admin.approval-workflows.index') }}"
                            class="menu-item group {{ Request::is('admin/approval-workflows*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <svg class="fill-current {{ Request::is('admin/approval-workflows*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M4.5 1.75C3.25736 1.75 2.25 2.75736 2.25 4V8C2.25 9.24264 3.25736 10.25 4.5 10.25H8C9.24264 10.25 10.25 9.24264 10.25 8V4C10.25 2.75736 9.24264 1.75 8 1.75H4.5ZM3.75 4C3.75 3.58579 4.08579 3.25 4.5 3.25H8C8.41421 3.25 8.75 3.58579 8.75 4V8C8.75 8.41421 8.41421 8.75 8 8.75H4.5C4.08579 8.75 3.75 8.41421 3.75 8V4ZM4.5 13.75C3.25736 13.75 2.25 14.7574 2.25 16V20C2.25 21.2426 3.25736 22.25 4.5 22.25H8C9.24264 22.25 10.25 21.2426 10.25 20V16C10.25 14.7574 9.24264 13.75 8 13.75H4.5ZM3.75 16C3.75 15.5858 4.08579 15.25 4.5 15.25H8C8.41421 15.25 8.75 15.5858 8.75 16V20C8.75 20.4142 8.41421 20.75 8 20.75H4.5C4.08579 20.75 3.75 20.4142 3.75 20V16ZM13.75 4C13.75 2.75736 14.7574 1.75 16 1.75H19.5C20.7426 1.75 21.75 2.75736 21.75 4V8C21.75 9.24264 20.7426 10.25 19.5 10.25H16C14.7574 10.25 13.75 9.24264 13.75 8V4ZM16 3.25C15.5858 3.25 15.25 3.58579 15.25 4V8C15.25 8.41421 15.5858 8.75 16 8.75H19.5C19.9142 8.75 20.25 8.41421 20.25 8V4C20.25 3.58579 19.9142 3.25 19.5 3.25H16ZM16 13.75C14.7574 13.75 13.75 14.7574 13.75 16V20C13.75 21.2426 14.7574 22.25 16 22.25H19.5C20.7426 22.25 21.75 21.2426 21.75 20V16C21.75 14.7574 20.7426 13.75 19.5 15.25H16C15.5858 20.75 15.25 20.4142 15.25 20V16Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Workflow Templates</span>
                            </span>
                        </a>
                    </li>
                    @endcan
                </ul>
            </div>
            @endcanany

            <!-- Group 3: SYSTEM ADMINISTRATION -->
            <div>
                <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
                    <span x-show="!sidebarToggle || sidebarHover" x-cloak>
                        SYSTEM ADMINISTRATION
                    </span>
                    <span x-show="sidebarToggle && !sidebarHover" x-cloak class="text-center text-[10px]">SYS</span>
                </h3>

                <ul class="flex flex-col gap-1.5">

                    <!-- User Management -->
                    @if (Auth::user()?->getActiveRole()?->name === 'admin')
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

                    <!-- User Roles -->
                    @if (Auth::user()?->getActiveRole()?->name === 'admin')
                    <li>
                        <a
                            href="{{ route('admin.roles.index') }}"
                            class="menu-item group {{ Request::is('admin/roles*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Shield/Roles Icon -->
                                <svg class="fill-current {{ Request::is('admin/roles*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M12 1.25C12.3039 1.25 12.586 1.43839 12.7022 1.72989L15.8559 9.18614L23.8711 10.4975C24.1956 10.5506 24.4544 10.7884 24.5373 11.1095C24.6202 11.4305 24.5114 11.7712 24.2613 11.975L18.0519 17.0318L20.1071 24.8435C20.195 25.1656 20.069 25.5046 19.7935 25.6782C19.518 25.8518 19.1584 25.8184 18.9187 25.598L12 20.7353L5.08126 25.598C4.84156 25.8184 4.48199 25.8518 4.20649 25.6782C3.93098 25.5046 3.805 25.1656 3.89291 24.8435L5.94813 17.0318L-0.261303 11.975C-0.51142 11.7712 -0.620182 11.4305 -0.537271 11.1095C-0.45436 10.7884 -0.195573 10.5506 0.128901 10.4975L8.14407 9.18614L11.2978 1.72989C11.414 1.43839 11.6961 1.25 12 1.25ZM7.57803 10.0603L12 4.11935L16.422 10.0603C16.5482 10.3439 16.8191 10.5304 17.1259 10.546L23.5819 11.4538L18.5613 15.5152C18.3227 15.7093 18.211 16.0169 18.2693 16.3183L19.8797 22.4354L14.0613 18.0033C13.8244 17.8318 13.516 17.7354 13.1969 17.7379L12 17.75L7.93872 18.0033C7.66284 18.0227 7.38129 17.9259 7.15884 17.7354L1.12031 22.4354L2.73069 16.3183C2.78903 16.0169 2.67733 15.7093 2.43872 15.5152L-2.58191 11.4538L3.87405 10.546C4.18089 10.5304 4.45181 10.3439 4.57803 10.0603Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>User Roles</span>
                            </span>
                        </a>
                    </li>
                    @endif

                    <!-- Company Management -->
                    @if (Auth::user()?->getActiveRole()?->name === 'admin')
                    <li>
                        <a
                            href="{{ route('admin.companies.index') }}"
                            class="menu-item group {{ Request::is('admin/companies*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Building Icon -->
                                <svg class="fill-current {{ Request::is('admin/companies*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2.75C4.75736 2.75 3.75 3.75736 3.75 5V19C3.75 19.4142 4.08579 19.75 4.5 19.75H19.5C19.9142 19.75 20.25 19.4142 20.25 19V5C20.25 3.75736 19.2426 2.75 18 2.75H6ZM5.25 5C5.25 4.58579 5.58579 4.25 6 4.25H18C18.4142 4.25 18.75 4.58579 18.75 5V18.25H5.25V5ZM8.75 8C8.75 7.58579 9.08579 7.25 9.5 7.25H10.5C10.9142 7.25 11.25 7.58579 11.25 8V8.5C11.25 8.9142 10.9142 9.25 10.5 9.25H9.5C9.08579 9.25 8.75 8.9142 8.75 8.5V8ZM9.5 11.25C9.08579 11.25 8.75 11.5858 8.75 12V12.5C8.75 12.9142 9.08579 13.25 9.5 13.25H10.5C10.9142 13.25 11.25 12.9142 11.25 12.5V12C11.25 11.5858 10.9142 11.25 10.5 11.25H9.5ZM13.5 7.25C13.0858 7.25 12.75 7.58579 12.75 8V8.5C12.75 8.9142 13.0858 9.25 13.5 9.25H14.5C14.9142 9.25 15.25 8.9142 15.25 8.5V8C15.25 7.58579 14.9142 7.25 14.5 7.25H13.5ZM13.5 11.25C13.0858 11.25 12.75 11.5858 12.75 12V12.5C12.75 12.9142 13.0858 13.25 13.5 13.25H14.5C14.9142 13.25 15.25 12.9142 15.25 12.5V12C15.25 11.5858 14.9142 11.25 14.5 11.25H13.5Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Company Management</span>
                            </span>
                        </a>
                    </li>
                    @endif

                    <!-- Org Structure -->
                    @if (Auth::user()?->getActiveRole()?->name === 'admin' || Gate::allows('manage-org-chart'))
                    <li>
                        <a
                            href="{{ route('admin.org-chart.index') }}"
                            class="menu-item group {{ Request::is('admin/org-chart*') ? 'menu-item-active' : 'menu-item-inactive' }}"
                        >
                            <span class="flex items-center gap-3">
                                <!-- Structure Icon -->
                                <svg class="fill-current {{ Request::is('admin/org-chart*') ? 'menu-item-icon-active' : 'menu-item-icon-inactive' }}" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M11 2C9.34315 2 8 3.34315 8 5V7H6C4.89543 7 4 7.89543 4 9V12C4 13.1046 4.89543 14 6 14H8V16C8 17.6569 9.34315 19 11 19H13C14.6569 19 16 17.6569 16 16V14H18C19.1046 14 20 13.1046 20 12V9C20 7.89543 19.1046 7 18 7H16V5C16 3.34315 14.6569 2 13 2H11ZM9.5 5C9.5 4.17157 10.1716 3.5 11 3.5H13C13.8284 3.5 14.5 4.17157 14.5 5V7H9.5V5ZM5.5 9C5.5 8.72386 5.72386 8.5 6 8.5H18C18.2761 8.5 18.5 8.72386 18.5 9V12C18.5 12.2761 18.2761 12.5 18 12.5H6C5.72386 12.5 5.5 12.2761 5.5 12V9ZM11 17.5C10.1716 17.5 9.5 16.8284 9.5 16V14H14.5V16C14.5 16.8284 13.8284 17.5 13 17.5H11Z" />
                                </svg>
                                <span class="menu-item-text" x-show="!sidebarToggle || sidebarHover" x-cloak>Org Structure</span>
                            </span>
                        </a>
                    </li>
                    @endif

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
