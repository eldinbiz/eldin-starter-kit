@extends('admin.settings.layout', [
    'title' => 'Edit Email Template: ' . $template->name,
    'heading' => 'Edit Email Template',
    'subheading' => 'Customize HTML content, placeholder tokens, and status for ' . $template->name,
])

@section('settings-content')
<div x-data="{
    previewModalOpen: false,
    previewSubject: '',
    previewHtml: '',
    previewLoading: false,

    async generatePreview() {
        this.previewModalOpen = true;
        this.previewLoading = true;
        try {
            const res = await fetch('{{ route('admin.settings.email-templates.preview', $template->id) }}');
            const data = await res.json();
            this.previewSubject = data.subject;
            this.previewHtml = data.html;
        } catch (e) {
            this.previewSubject = 'Error';
            this.previewHtml = '<p class=&quot;text-error-500&quot;>Failed to generate preview.</p>';
        } finally {
            this.previewLoading = false;
        }
    },

    insertToken(token) {
        const textarea = document.getElementById('body_html');
        if (!textarea) return;
        const text = `\{\{ ${token} \}\}`;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        textarea.value = textarea.value.substring(0, start) + text + textarea.value.substring(end);
        textarea.focus();
        textarea.selectionStart = textarea.selectionEnd = start + text.length;
    }
}">
    {{-- Header with Back Button --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a
                href="{{ route('admin.settings.email-templates.index', ['company_id' => $template->company_id]) }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-theme-xs hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                ← Back to Templates
            </a>
            <span class="text-xs font-mono text-gray-500">
                Trigger Event: <strong>{{ $template->event }}</strong>
            </span>
        </div>

        <button
            type="button"
            @click="generatePreview()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
        >
            <span>👁️</span>
            <span>Live Preview</span>
        </button>
    </div>

    {{-- Available Variables Token Bar --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
        <h4 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
            Available Placeholder Tokens (Click to insert into HTML)
        </h4>
        <div class="flex flex-wrap gap-2">
            @php
                $defaultTokens = ['user_name', 'user_email', 'company_name', 'title', 'request_title', 'message', 'action_url', 'approval_link', 'action_text', 'app_name', 'app_url', 'current_year'];
                $tokens = !empty($template->variables) ? array_unique(array_merge($defaultTokens, $template->variables)) : $defaultTokens;
            @endphp
            @foreach ($tokens as $tok)
                <button
                    type="button"
                    @click="insertToken('{{ $tok }}')"
                    class="inline-flex items-center gap-1 rounded-md border border-brand-200 bg-white px-2.5 py-1 font-mono text-xs text-brand-700 shadow-theme-xs hover:bg-brand-50 dark:border-brand-800 dark:bg-gray-800 dark:text-brand-400 dark:hover:bg-brand-500/10"
                >
                    <span>+</span>
                    <span>&#123;&#123; {{ $tok }} &#125;&#125;</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Edit Form --}}
    <form action="{{ route('admin.settings.email-templates.update', $template->id) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Subject --}}
        <div>
            <label for="subject" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Email Subject Pattern <span class="text-error-500">*</span>
            </label>
            <input
                type="text"
                id="subject"
                name="subject"
                value="{{ old('subject', $template->subject) }}"
                required
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p class="mt-1 text-xs text-gray-500">Supports variable tokens, e.g. <code>[@{{ company_name }}] Approval Requested: @{{ title }}</code></p>
        </div>

        {{-- HTML Body --}}
        <div>
            <label for="body_html" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                HTML Email Body Content <span class="text-error-500">*</span>
            </label>
            <textarea
                id="body_html"
                name="body_html"
                rows="14"
                required
                class="w-full rounded-lg border border-gray-300 bg-transparent p-4 font-mono text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
            >{{ old('body_html', $template->body_html) }}</textarea>
        </div>

        {{-- Plain Text Body (Optional) --}}
        <div>
            <label for="body_text" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Plain Text Version (Optional Fallback)
            </label>
            <textarea
                id="body_text"
                name="body_text"
                rows="4"
                class="w-full rounded-lg border border-gray-300 bg-transparent p-3 font-mono text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
            >{{ old('body_text', $template->body_text) }}</textarea>
        </div>

        {{-- Active Toggle --}}
        <div>
            <label class="inline-flex items-center gap-2.5 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    {{ old('is_active', $template->is_active) ? 'checked' : '' }}
                    class="h-4 w-4 rounded text-brand-600 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900"
                />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Template Active (Uncheck to temporarily disable this template)
                </span>
            </label>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-800">
            <a
                href="{{ route('admin.settings.email-templates.index', ['company_id' => $template->company_id]) }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
            >
                Cancel
            </a>
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
            >
                Save Template
            </button>
        </div>
    </form>

    {{-- Live Preview Modal --}}
    <div
        x-show="previewModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="previewModalOpen = false"
    >
        <div
            @click.outside="previewModalOpen = false"
            class="w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="mb-4 flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <div>
                    <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                        Template Live Preview
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`Subject: ${previewSubject}`"></p>
                </div>
                <button
                    type="button"
                    @click="previewModalOpen = false"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-500 dark:hover:bg-gray-800"
                >
                    ✕
                </button>
            </div>

            <div class="max-h-96 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-950">
                <template x-if="previewLoading">
                    <div class="py-12 text-center text-xs text-gray-400">
                        Generating preview with dummy data...
                    </div>
                </template>
                <div x-show="!previewLoading" x-html="previewHtml"></div>
            </div>

            <div class="mt-4 flex justify-end">
                <button
                    type="button"
                    @click="previewModalOpen = false"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                >
                    Close Preview
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
