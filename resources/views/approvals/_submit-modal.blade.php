@props(['approvable', 'submitRoute', 'templates', 'previewRoute'])

<div 
    x-data="approvalSubmitModal('{{ $previewRoute }}', '{{ $templates->first()?->id }}')" 
    x-on:template-change.window="selectedTemplate = $event.detail; loadPreview();"
>

    {{-- Trigger button --}}
    <button type="button" @click="open = true"
        class="inline-flex items-center justify-center gap-1 rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">
        Submit for Approval
    </button>

    {{-- Modal overlay --}}
    <div x-show="open" x-cloak x-trap.noscroll="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div @click.outside="open = false"
            class="mx-4 w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-white/[0.03]">

            <p class="text-lg font-semibold text-gray-800 dark:text-white/90 mb-1">
                Submit for Approval
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                {{ $approvable->getApprovalTitle() }}
            </p>

            {{-- Template selector --}}
            @if ($templates->count() > 1)
                <div class="mb-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Workflow Template</label>
                    <select x-model="selectedTemplate" @change="loadPreview()"
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                        @foreach ($templates as $template)
                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif ($templates->count() === 1)
                <input type="hidden" x-model="selectedTemplate" value="{{ $templates->first()->id }}">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Template: <strong>{{ $templates->first()->name }}</strong>
                </p>
            @else
                <div class="mb-4 rounded-lg bg-warning-50 p-3 text-center dark:bg-warning-500/10">
                    <p class="text-sm text-warning-700 dark:text-warning-400">
                        No active workflow templates configured. Please contact an administrator.
                    </p>
                </div>
            @endif

            {{-- Workflow preview --}}
            <div class="mb-4 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2 uppercase">Approval Workflow Preview</p>
                <div x-show="loading" class="py-4 text-center">
                    <p class="text-sm text-gray-400">Loading workflow...</p>
                </div>
                <div x-show="!loading" x-html="previewHtml"></div>
            </div>

            {{-- Actions --}}
            <div class="flex justify-end gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
                <button type="button" @click="open = false"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300">
                    Cancel
                </button>

                @if ($templates->isNotEmpty())
                    <form action="{{ $submitRoute }}" method="POST" @submit="submitted = true">
                        @csrf
                        <input type="hidden" name="template_id" x-model="selectedTemplate">
                        <button type="submit" :disabled="submitted"
                            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-50 disabled:cursor-not-allowed">
                            Confirm & Submit
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const registerModal = () => {
            if (typeof Alpine === 'undefined') return;

            Alpine.data('approvalSubmitModal', (previewRoute = '', initialTemplateId = null) => ({
                open: false,
                selectedTemplate: initialTemplateId,
                previewHtml: '',
                loading: false,
                submitted: false,

                init() {
                    this.$watch('open', (val) => {
                        if (val && !this.previewHtml) {
                            this.loadPreview();
                        }
                    });
                },

                async loadPreview() {
                    if (!this.selectedTemplate || !previewRoute) return;
                    this.loading = true;
                    try {
                        const url = previewRoute + '?template_id=' + this.selectedTemplate;
                        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        this.previewHtml = await resp.text();
                    } catch (e) {
                        this.previewHtml = '<p class="text-sm text-error-500">Failed to load workflow preview.</p>';
                    }
                    this.loading = false;
                }
            }));
        };

        if (window.Alpine) {
            registerModal();
        } else {
            document.addEventListener('alpine:init', registerModal);
        }
    })();
</script>

