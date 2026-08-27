<div class="space-y-6">
    {{-- Header & Period Selector --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                Dashboard Overview
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                @if ($this->activeCompany)
                    Showing metrics for <strong class="text-gray-800 dark:text-gray-200">{{ $this->activeCompany->name }}</strong>
                @else
                    Global System Analytics
                @endif
            </p>
        </div>

        {{-- Period Tabs --}}
        <div class="flex items-center gap-1 p-1 rounded-xl bg-gray-100 dark:bg-gray-800 text-xs font-medium self-start sm:self-auto">
            @foreach (['7d' => '7 Days', '30d' => '30 Days', '90d' => 'Quarter', '1y' => 'Year'] as $key => $label)
                <button
                    type="button"
                    wire:click="setPeriod('{{ $key }}')"
                    class="px-3 py-1.5 rounded-lg transition cursor-pointer {{ $period === $key ? 'bg-white text-brand-600 shadow-xs font-semibold dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- Row 1: Metrics Counters --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4 md:gap-6">
        {{-- Counter 1: Active Users --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 transition hover:border-brand-500/40">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                    Active
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Company Users</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($this->metrics['users_count']) }}
                </h4>
            </div>
        </div>

        {{-- Counter 2: Pending Approvals --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 transition hover:border-amber-500/40">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <a
                    href="{{ route('approvals.inbox') }}"
                    wire:navigate
                    class="text-xs font-medium text-amber-600 hover:underline dark:text-amber-400"
                >
                    Review &rarr;
                </a>
            </div>
            <div class="mt-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Approvals</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($this->metrics['pending_approvals']) }}
                </h4>
            </div>
        </div>

        {{-- Counter 3: Procurement Total Spend --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 transition hover:border-emerald-500/40">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                    </svg>
                </div>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $this->metrics['pr_count'] }} PRs
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Procurement Volume</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    ${{ number_format($this->metrics['total_spend'], 2) }}
                </h4>
            </div>
        </div>

        {{-- Counter 4: Active API Tokens --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 transition hover:border-purple-500/40">
            <div class="flex items-center justify-between">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                    </svg>
                </div>
                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-semibold text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">
                    Sanctum
                </span>
            </div>
            <div class="mt-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Active API Tokens</span>
                <h4 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($this->metrics['api_tokens_count']) }}
                </h4>
            </div>
        </div>
    </div>

    {{-- Row 2: Interactive Charts with Alpine.js + ApexCharts --}}
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        {{-- Chart 1: Activity Volume Area Chart --}}
        <div class="col-span-12 xl:col-span-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">System Activity &amp; Submissions</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Operational volume across {{ $period }}</p>
                </div>
            </div>
            <div
                x-data="chartComponent({
                    type: 'area',
                    categories: {{ json_encode($this->chartData['categories']) }},
                    series: [{ name: 'System Events', data: {{ json_encode($this->chartData['activity']) }} }],
                    colors: ['#465FFF'],
                    height: 300
                })"
                class="w-full min-h-[300px]"
            ></div>
        </div>

        {{-- Chart 2: Status Share Donut Chart --}}
        <div class="col-span-12 xl:col-span-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Approval Status Share</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Decision breakdown</p>
                </div>
            </div>
            <div
                x-data="donutChartComponent({
                    series: [
                        {{ $this->chartData['approvalDistribution']['approved'] }},
                        {{ $this->chartData['approvalDistribution']['pending'] }},
                        {{ $this->chartData['approvalDistribution']['rejected'] }}
                    ],
                    labels: ['Approved', 'Pending', 'Rejected'],
                    colors: ['#10B981', '#F59E0B', '#EF4444'],
                    height: 280
                })"
                class="flex items-center justify-center min-h-[280px]"
            ></div>
        </div>
    </div>

    {{-- Row 3: Recent Activity Stream & Action Shortcuts --}}
    <div class="grid grid-cols-12 gap-4 md:gap-6">
        {{-- Recent Activity List --}}
        <div class="col-span-12 xl:col-span-8 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Recent System Activity</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Latest actions and audit events</p>
                </div>
                @feature('audit_logs')
                @can('view-audit-logs')
                    <a
                        href="{{ route('admin.audit-trail.index') }}"
                        wire:navigate
                        class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400"
                    >
                        View Full Audit Trail &rarr;
                    </a>
                @endcan
                @endfeature
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($this->recentActivities as $act)
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $act['type'] === 'created' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400' : ($act['type'] === 'deleted' ? 'bg-rose-50 text-rose-600 dark:bg-rose-500/10 dark:text-rose-400' : 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400') }}">
                                {{ strtoupper(substr($act['type'], 0, 1)) }}
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">
                                    {{ $act['title'] }}
                                </p>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                                    Actor: <span class="font-medium text-gray-700 dark:text-gray-300">{{ $act['actor'] }}</span>
                                </p>
                            </div>
                        </div>
                        <span class="text-[11px] text-gray-400 whitespace-nowrap">
                            {{ $act['time'] }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Quick Operations Card --}}
        <div class="col-span-12 xl:col-span-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] md:p-6 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Quick Actions</h3>

            <div class="space-y-2">
                @feature('purchase_requests')
                    <a
                        href="{{ route('purchase-requests.create') }}"
                        wire:navigate
                        class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-white hover:border-brand-500 transition dark:border-gray-800 dark:bg-gray-800/30 dark:hover:bg-gray-800/70"
                    >
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-lg bg-brand-500 text-white flex items-center justify-center text-sm font-bold">+</span>
                            <div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">New Purchase Request</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Multi-step procurement wizard</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">&rarr;</span>
                    </a>
                @endfeature

                @feature('approvals')
                    <a
                        href="{{ route('approvals.inbox') }}"
                        wire:navigate
                        class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-white hover:border-amber-500 transition dark:border-gray-800 dark:bg-gray-800/30 dark:hover:bg-gray-800/70"
                    >
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-lg bg-amber-500 text-white flex items-center justify-center text-sm font-bold">✓</span>
                            <div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">Pending Approvals</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Review &amp; approve waiting steps</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">&rarr;</span>
                    </a>
                @endfeature

                @can('manage-api-tokens')
                    <a
                        href="{{ route('admin.api-tokens.index') }}"
                        wire:navigate
                        class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50/50 hover:bg-white hover:border-purple-500 transition dark:border-gray-800 dark:bg-gray-800/30 dark:hover:bg-gray-800/70"
                    >
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-lg bg-purple-500 text-white flex items-center justify-center text-sm font-bold">&lt;/&gt;</span>
                            <div>
                                <p class="text-xs font-semibold text-gray-900 dark:text-white">API Token Governance</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">Generate &amp; manage Sanctum tokens</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">&rarr;</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chartComponent', (config) => ({
            chart: null,
            init() {
                if (typeof ApexCharts === 'undefined') return;

                const options = {
                    chart: {
                        type: config.type || 'area',
                        height: config.height || 300,
                        toolbar: { show: false },
                        fontFamily: 'inherit',
                    },
                    series: config.series || [],
                    xaxis: {
                        categories: config.categories || [],
                        labels: { style: { colors: '#9CA3AF', fontSize: '11px' } }
                    },
                    colors: config.colors || ['#465FFF'],
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.05 } },
                    grid: { borderColor: '#E5E7EB', strokeDashArray: 3 },
                    markers: { size: 3 }
                };

                this.chart = new ApexCharts(this.$el, options);
                this.chart.render();
            },
            destroy() {
                if (this.chart) {
                    this.chart.destroy();
                }
            }
        }));

        Alpine.data('donutChartComponent', (config) => ({
            chart: null,
            init() {
                if (typeof ApexCharts === 'undefined') return;

                const options = {
                    chart: {
                        type: 'donut',
                        height: config.height || 280,
                        fontFamily: 'inherit',
                    },
                    series: config.series || [],
                    labels: config.labels || [],
                    colors: config.colors || ['#10B981', '#F59E0B', '#EF4444'],
                    legend: { position: 'bottom', fontSize: '12px' },
                    plotOptions: { pie: { donut: { size: '65%' } } },
                    dataLabels: { enabled: false }
                };

                this.chart = new ApexCharts(this.$el, options);
                this.chart.render();
            },
            destroy() {
                if (this.chart) {
                    this.chart.destroy();
                }
            }
        }));
    });
</script>
