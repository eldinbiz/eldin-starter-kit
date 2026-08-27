<div class="space-y-6">
    {{-- Header & Quick Links --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                REST API Explorer &amp; OpenAPI Console
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                Test live endpoints, inspect OpenAPI schemas, and generate client integration code.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a
                href="{{ url('/docs/api') }}"
                target="_blank"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <span>Scramble Docs (Full UI)</span>
                <svg class="w-3.5 h-3.5 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                </svg>
            </a>
        </div>
    </div>

    {{-- Tag Filters --}}
    <div class="flex flex-wrap items-center gap-1.5 border-b border-gray-200 pb-3 dark:border-gray-800 text-xs">
        <span class="font-medium text-gray-400 mr-1">Modules:</span>
        @foreach (['all' => 'All Modules', 'auth' => 'Auth', 'users' => 'Users', 'roles' => 'Roles', 'companies' => 'Companies', 'approvals' => 'Approvals', 'notifications' => 'Notifications', 'audit logs' => 'Audit Logs'] as $tagKey => $tagLabel)
            <button
                type="button"
                wire:click="setTag('{{ $tagKey }}')"
                class="px-3 py-1 rounded-full transition cursor-pointer {{ strtolower($selectedTag) === strtolower($tagKey) ? 'bg-brand-500 text-white font-semibold shadow-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}"
            >
                {{ $tagLabel }}
            </button>
        @endforeach
    </div>

    {{-- Main 2-Column Console Layout --}}
    <div class="grid grid-cols-12 gap-6 items-start">
        {{-- Left: Endpoint Navigation List --}}
        <div class="col-span-12 lg:col-span-4 rounded-2xl border border-gray-200 bg-white p-3 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] space-y-1 max-h-[750px] overflow-y-auto">
            <div class="px-2 py-1.5 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                Endpoints ({{ count($this->filteredEndpoints) }})
            </div>

            @foreach ($this->filteredEndpoints as $key => $ep)
                <button
                    type="button"
                    wire:click="selectEndpoint('{{ $key }}')"
                    class="w-full text-left p-2.5 rounded-xl transition flex items-center justify-between gap-2 cursor-pointer {{ $selectedEndpointKey === $key ? 'bg-brand-50 border border-brand-500/30 text-brand-900 dark:bg-brand-500/10 dark:text-brand-300 dark:border-brand-500/30' : 'hover:bg-gray-50 text-gray-700 dark:text-gray-300 dark:hover:bg-gray-800/50' }}"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center justify-center px-1.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $ep['method'] === 'GET' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/20 dark:text-emerald-300' : ($ep['method'] === 'POST' ? 'bg-blue-100 text-blue-800 dark:bg-blue-500/20 dark:text-blue-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/20 dark:text-amber-300') }}">
                                {{ $ep['method'] }}
                            </span>
                            <span class="text-xs font-mono truncate font-medium">
                                {{ $ep['path'] }}
                            </span>
                        </div>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                            {{ $ep['summary'] }}
                        </p>
                    </div>
                </button>
            @endforeach
        </div>

        {{-- Right: Endpoint Workbench & Live Runner --}}
        <div class="col-span-12 lg:col-span-8 space-y-6">
            @php $current = $this->currentEndpoint; @endphp

            {{-- Workbench Card --}}
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] space-y-5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-bold uppercase text-emerald-700 ring-1 ring-inset ring-emerald-600/20 dark:bg-emerald-500/10 dark:text-emerald-400">
                                {{ $current['method'] }}
                            </span>
                            <h4 class="font-mono text-base font-semibold text-gray-900 dark:text-white">
                                {{ $current['path'] }}
                            </h4>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            {{ $current['description'] }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 self-start sm:self-auto">
                        <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 text-[11px] font-medium text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">
                            {{ $current['ability'] }}
                        </span>
                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ $current['scope'] }}
                        </span>
                    </div>
                </div>

                {{-- Request Parameters & Headers Form --}}
                <div class="space-y-4">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        Request Configuration
                    </h5>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Header: X-Company-ID (Tenant Scope)
                            </label>
                            <input
                                type="text"
                                wire:model.live="requestHeaders.X-Company-ID"
                                placeholder="e.g. 1"
                                class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                            />
                        </div>

                        {{-- Path Parameters --}}
                        @foreach ($pathParams as $paramKey => $paramVal)
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Path Param: <code>{ {{ $paramKey }} }</code>
                                </label>
                                <input
                                    type="text"
                                    wire:model.live="pathParams.{{ $paramKey }}"
                                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                        @endforeach

                        {{-- Query Parameters --}}
                        @foreach ($queryParams as $qKey => $qVal)
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Query Param: <code>?{{ $qKey }}</code>
                                </label>
                                <input
                                    type="text"
                                    wire:model.live="queryParams.{{ $qKey }}"
                                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Code Snippet Generator --}}
                <div class="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Client Code Snippet
                        </span>
                        <div class="flex items-center gap-1">
                            @foreach (['curl' => 'cURL', 'js' => 'JavaScript', 'php' => 'PHP', 'python' => 'Python'] as $langKey => $langLabel)
                                <button
                                    type="button"
                                    wire:click="setSnippetLanguage('{{ $langKey }}')"
                                    class="px-2 py-0.5 rounded text-[11px] font-medium transition cursor-pointer {{ $snippetLanguage === $langKey ? 'bg-brand-500 text-white' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white' }}"
                                >
                                    {{ $langLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-900 p-4 text-xs font-mono text-gray-200 overflow-x-auto">
                        <pre><code>{{ $this->generateSnippet() }}</code></pre>
                    </div>
                </div>

                {{-- Action: Send Request --}}
                <div class="flex items-center justify-between pt-2">
                    <button
                        type="button"
                        wire:click="executeRequest"
                        wire:loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-xs font-semibold text-white shadow-theme-xs hover:bg-brand-500 transition cursor-pointer"
                    >
                        <svg wire:loading.remove wire:target="executeRequest" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                        <svg wire:loading wire:target="executeRequest" class="w-4 h-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Send API Request</span>
                    </button>

                    @if ($responseStatus)
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold {{ $responseStatus < 300 ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400' }}">
                                HTTP {{ $responseStatus }}
                            </span>
                            <span class="text-xs text-gray-400">
                                {{ $responseDurationMs }} ms
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Live Response Viewer --}}
            @if ($responseBody !== null)
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03] space-y-3">
                    <div class="flex items-center justify-between">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Response Payload
                        </h5>
                        <span class="text-[11px] font-mono text-gray-400">application/json</span>
                    </div>

                    <div class="rounded-xl bg-gray-900 p-4 text-xs font-mono text-emerald-400 max-h-[400px] overflow-y-auto overflow-x-auto">
                        <pre><code>{{ $responseBody }}</code></pre>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
