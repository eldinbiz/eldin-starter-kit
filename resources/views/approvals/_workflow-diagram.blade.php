@if (empty($preview['groups']))
    <p class="text-sm text-gray-500 dark:text-gray-400">No workflow configured.</p>
@else
    <div class="space-y-4">
        @foreach ($preview['groups'] as $groupIndex => $group)
            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-white/[0.02]">
                <div class="flex items-center gap-2 mb-2">
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">
                        Group {{ $groupIndex + 1 }}: {{ $group['name'] }}
                    </span>
                    <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ $group['type'] }}
                    </span>
                </div>

                <div class="space-y-2">
                    @foreach ($group['steps'] as $stepIndex => $step)
                        @if ($stepIndex > 0)
                            <div class="text-center text-gray-400 dark:text-gray-500">↓</div>
                        @endif
                        <div class="rounded-lg border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-white/[0.03]">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 text-xs font-medium text-brand-500 dark:text-brand-400">{{ $stepIndex + 1 }}</span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-800 dark:text-white/90">
                                        {{ $step['label'] ?: 'Step ' . ($stepIndex + 1) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        Approver: <strong>{{ $step['approver_name'] }}</strong>
                                        @if ($step['approver_job_title'])
                                            — {{ $step['approver_job_title'] }}
                                        @endif
                                    </p>
                                    <p class="text-[10px] text-gray-400 dark:text-gray-500">
                                        Type: {{ $step['resolver_type'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif
