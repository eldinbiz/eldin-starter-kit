@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div x-data="{ pageName: 'Dashboard' }">
    @include('partials.breadcrumb')
</div>

{{-- Row 1: Counters --}}
<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
    {{-- Counter 1: Unique Views --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 dark:bg-brand-500/10">
            <svg class="fill-brand-500 dark:fill-brand-400" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
            </svg>
        </div>
        <div class="mt-5 flex items-end justify-between">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Unique Views</span>
                <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">24,532</h4>
            </div>
            <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.31631 1.37415 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.12329 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C2.59484 4.59182 5.56462 1.62393 5.56462 1.62393Z"/>
                </svg>
                +12.5%
            </span>
        </div>
    </div>

    {{-- Counter 2: Total Revenue --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-success-50 dark:bg-success-500/10">
            <svg class="fill-success-600 dark:fill-success-400" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H7c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.04-.42 1.99-1.07 2.75z" class="hidden"/>
                {{-- Dollar sign vector --}}
                <path d="M12 2a10 10 0 100 20 10 10 0 000-20zm1.375 12.875c0 1.25-.875 2-2.125 2.25v1h-1.5v-1c-1.375-.125-2.375-.875-2.5-2.25h1.75c.125.625.625.875 1.25.875.625 0 1-.25 1-.625 0-.375-.25-.625-1-.875l-1-.375c-1.25-.5-1.75-1.125-1.75-2.125 0-1.125.75-1.875 2-2.125v-1h1.5v1c1.125.125 1.875.75 2 1.75h-1.75c-.125-.5-.5-.625-1-.625-.5 0-.875.125-.875.5s.25.5.875.75l1 .375c1.25.5 1.75 1.125 1.75 2.125z" fill="currentColor"/>
            </svg>
        </div>
        <div class="mt-5 flex items-end justify-between">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Spent</span>
                <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">$14,352.20</h4>
            </div>
            <span class="flex items-center gap-1 rounded-full bg-error-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-error-600 dark:bg-error-500/15 dark:text-error-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C6.0663 10.6259 6.40505 10.4062L9.40514 7.4082C8.88785 6.05438 8.59486 6.34717 6.62329 8.06753L6.62329 1.875C6.62329 1.46079 5.87329 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.875 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C2.34484 7.40818 5.31462 10.3761 5.31462 10.3761Z"/>
                </svg>
                -2.4%
            </span>
        </div>
    </div>

    {{-- Counter 3: Total Orders --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 dark:bg-orange-500/10">
            <svg class="fill-orange-500 dark:fill-orange-400" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
            </svg>
        </div>
        <div class="mt-5 flex items-end justify-between">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Orders</span>
                <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">5,359</h4>
            </div>
            <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.31631 1.37415 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.12329 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C2.59484 4.59182 5.56462 1.62393 5.56462 1.62393Z"/>
                </svg>
                +8.1%
            </span>
        </div>
    </div>

    {{-- Counter 4: Customers --}}
    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-light-50 dark:bg-blue-light-500/10">
            <svg class="fill-blue-light-600 dark:fill-blue-light-400" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>
            </svg>
        </div>
        <div class="mt-5 flex items-end justify-between">
            <div>
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Users</span>
                <h4 class="mt-2 text-title-sm font-bold text-gray-800 dark:text-white/90">3,782</h4>
            </div>
            <span class="flex items-center gap-1 rounded-full bg-success-50 py-0.5 pl-2 pr-2.5 text-sm font-medium text-success-600 dark:bg-success-500/15 dark:text-success-500">
                <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.31631 1.37415 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.12329 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C2.59484 4.59182 5.56462 1.62393 5.56462 1.62393Z"/>
                </svg>
                +4.3%
            </span>
        </div>
    </div>
</div>

{{-- Row 2: Charts --}}
<div class="grid grid-cols-12 gap-4 md:gap-6 mt-6">
    {{-- Chart 1: Line / Area Chart (Traffic Analytics) --}}
    <div class="col-span-12 xl:col-span-8 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Traffic Analytics</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Monthly traffic performance</p>
            </div>
        </div>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div x-data x-init="
                const options = {
                    chart: { type: 'area', height: 310, toolbar: { show: false } },
                    series: [{ name: 'Unique Visitors', data: [30, 45, 38, 55, 48, 65, 75, 70, 95, 88, 110, 125] }],
                    xaxis: { categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] },
                    colors: ['#465FFF'],
                    stroke: { curve: 'smooth', width: 3 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
                    grid: { borderColor: '#f1f1f1' },
                    markers: { size: 4, colors: ['#465FFF'], strokeColors: '#fff', strokeWidth: 2 }
                };
                new ApexCharts($el, options).render();
            " class="w-full min-w-[500px]"></div>
        </div>
    </div>

    {{-- Chart 2: Donut / Pie Chart (Device Distribution) --}}
    <div class="col-span-12 xl:col-span-4 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Device Share</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Visitor session sources</p>
            </div>
        </div>
        <div class="flex items-center justify-center h-[310px]">
            <div x-data x-init="
                const options = {
                    chart: { type: 'donut', height: 280 },
                    series: [55, 30, 15],
                    labels: ['Desktop', 'Mobile', 'Tablet'],
                    colors: ['#465FFF', '#0ba5ec', '#fdb022'],
                    legend: { position: 'bottom' },
                    plotOptions: { pie: { donut: { size: '65%' } } },
                    dataLabels: { enabled: false }
                };
                new ApexCharts($el, options).render();
            " class="w-full"></div>
        </div>
    </div>

    {{-- Chart 3: Bar Chart (Sales Overview) --}}
    <div class="col-span-12 rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 mt-2">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Sales Revenue</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">Weekly sales overview</p>
            </div>
        </div>
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <div x-data x-init="
                const options = {
                    chart: { type: 'bar', height: 300, toolbar: { show: false } },
                    series: [
                        { name: 'Revenue', data: [44, 55, 41, 67, 22, 43, 65] },
                        { name: 'Sales count', data: [13, 23, 20, 8, 13, 27, 33] }
                    ],
                    xaxis: { categories: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] },
                    colors: ['#465FFF', '#0ba5ec'],
                    plotOptions: { bar: { horizontal: false, columnWidth: '45%', borderRadius: 4 } },
                    fill: { opacity: 1 },
                    grid: { borderColor: '#f1f1f1' }
                };
                new ApexCharts($el, options).render();
            " class="w-full min-w-[600px]"></div>
        </div>
    </div>
</div>

{{-- Row 3: Recent Transactions --}}
<div class="rounded-2xl border border-gray-200 bg-white px-4 pb-3 pt-4 dark:border-gray-800 dark:bg-white/[0.03] sm:px-6 mt-6">
    <div class="flex flex-col gap-2 mb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Recent Transactions</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Latest wallet and invoice actions</p>
        </div>
    </div>

    <div class="w-full overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="border-gray-100 border-y dark:border-gray-800">
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Transaction ID</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Customer</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Date</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Amount</p>
                    </th>
                    <th class="py-3 text-left">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                {{-- Transaction 1 --}}
                <tr>
                    <td class="py-3.5">
                        <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">#TXN-1284</p>
                    </td>
                    <td class="py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">AD</span>
                            <div>
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">Alex Demidov</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">alex@example.com</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5">
                        <p class="text-gray-700 text-theme-sm dark:text-gray-300">July 09, 2026</p>
                    </td>
                    <td class="py-3.5">
                        <p class="font-semibold text-success-600 dark:text-success-500 text-theme-sm">+$450.00</p>
                    </td>
                    <td class="py-3.5">
                        <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">
                            Completed
                        </span>
                    </td>
                </tr>

                {{-- Transaction 2 --}}
                <tr>
                    <td class="py-3.5">
                        <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">#TXN-1283</p>
                    </td>
                    <td class="py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="h-9 w-9 rounded-full bg-orange-50 flex items-center justify-center text-orange-600 font-bold text-xs dark:bg-orange-500/10 dark:text-orange-400">SL</span>
                            <div>
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">Sarah Laine</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">sarah.l@example.com</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5">
                        <p class="text-gray-700 text-theme-sm dark:text-gray-300">July 08, 2026</p>
                    </td>
                    <td class="py-3.5">
                        <p class="font-semibold text-gray-700 dark:text-gray-300 text-theme-sm">-$120.50</p>
                    </td>
                    <td class="py-3.5">
                        <span class="inline-flex rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-semibold text-warning-600 dark:bg-warning-500/15 dark:text-warning-500">
                            Pending
                        </span>
                    </td>
                </tr>

                {{-- Transaction 3 --}}
                <tr>
                    <td class="py-3.5">
                        <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">#TXN-1282</p>
                    </td>
                    <td class="py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="h-9 w-9 rounded-full bg-error-50 flex items-center justify-center text-error-600 font-bold text-xs dark:bg-error-500/10 dark:text-error-400">MK</span>
                            <div>
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">Michael Kress</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">m.kress@example.com</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5">
                        <p class="text-gray-700 text-theme-sm dark:text-gray-300">July 06, 2026</p>
                    </td>
                    <td class="py-3.5">
                        <p class="font-semibold text-error-600 dark:text-error-500 text-theme-sm">+$89.00</p>
                    </td>
                    <td class="py-3.5">
                        <span class="inline-flex rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-semibold text-error-600 dark:bg-error-500/15 dark:text-error-400">
                            Failed
                        </span>
                    </td>
                </tr>

                {{-- Transaction 4 --}}
                <tr>
                    <td class="py-3.5">
                        <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">#TXN-1281</p>
                    </td>
                    <td class="py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="h-9 w-9 rounded-full bg-blue-light-50 flex items-center justify-center text-blue-light-600 font-bold text-xs dark:bg-blue-light-500/10 dark:text-blue-light-400">EH</span>
                            <div>
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">Emma Hales</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">emma.hales@example.com</p>
                            </div>
                        </div>
                    </td>
                    <td class="py-3.5">
                        <p class="text-gray-700 text-theme-sm dark:text-gray-300">July 05, 2026</p>
                    </td>
                    <td class="py-3.5">
                        <p class="font-semibold text-success-600 dark:text-success-500 text-theme-sm">+$1,200.00</p>
                    </td>
                    <td class="py-3.5">
                        <span class="inline-flex rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-semibold text-success-600 dark:bg-success-500/15 dark:text-success-500">
                            Completed
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
@endsection
