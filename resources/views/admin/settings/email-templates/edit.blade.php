@extends('admin.settings.layout', [
    'title' => 'Edit Email Template: ' . $template->name,
    'heading' => 'Edit Email Template',
    'subheading' => 'Customize template content, placeholder tokens, and status for ' . $template->name,
])

@section('settings-content')
{{-- Quill Snow & HTML Source Editor Assets via CDN --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-html-edit-button@2.2.7/dist/quill.htmlEditButton.min.js"></script>

<div x-data="emailTemplateEditor({
    companyId: '{{ $template->company_id ?? '' }}',
    previewDraftRoute: '{{ route('admin.settings.email-templates.preview-draft') }}',
    csrfToken: '{{ csrf_token() }}'
})">
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
            @if ($template->company)
                <span class="inline-flex items-center rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-700/10 dark:bg-brand-500/10 dark:text-brand-400">
                    🏢 {{ $template->company->name }} Override
                </span>
            @else
                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400">
                    🌐 Global Default
                </span>
            @endif
        </div>

        <button
            type="button"
            @click="generateLivePreview()"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
        >
            <span>👁️</span>
            <span>Live Draft Preview</span>
        </button>
    </div>

    {{-- Available Variables Token Bar --}}
    <div class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
        <div class="mb-2 flex items-center justify-between">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                Placeholder Tokens (Click to insert at cursor)
            </h4>
            <span class="text-xs font-medium text-brand-600 dark:text-brand-400" x-text="`Targeting: ${lastActiveField === 'subject' ? 'Subject Line' : (bodyTab === 'text' ? 'Plain Text Fallback' : 'Rich HTML (Quill)')}`"></span>
        </div>
        <div class="flex flex-wrap gap-2">
            @php
                $defaultTokens = ['user_name', 'user_email', 'company_name', 'title', 'request_title', 'message', 'action_url', 'approval_link', 'action_text', 'app_name', 'current_year'];
                $tokens = !empty($template->variables) ? array_unique(array_merge($defaultTokens, $template->variables)) : $defaultTokens;
            @endphp
            @foreach ($tokens as $tok)
                <button
                    type="button"
                    @click.prevent="insertToken('{{ $tok }}')"
                    class="inline-flex items-center gap-1 rounded-md border border-brand-200 bg-white px-2.5 py-1 font-mono text-xs text-brand-700 shadow-theme-xs hover:bg-brand-50 dark:border-brand-800 dark:bg-gray-800 dark:text-brand-400 dark:hover:bg-brand-500/10 cursor-pointer"
                >
                    <span>+</span>
                    <span>&#123;&#123; {{ $tok }} &#125;&#125;</span>
                </button>
            @endforeach
        </div>
    </div>

    {{-- Edit Form --}}
    <form action="{{ route('admin.settings.email-templates.update', $template->id) }}" method="POST" @submit="syncAndSubmit()" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Hidden textarea holding the actual raw HTML value --}}
        <textarea name="body_html" x-ref="hiddenBodyHtml" class="hidden">{{ old('body_html', $template->body_html) }}</textarea>

        {{-- Name --}}
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Template Display Name <span class="text-error-500">*</span>
            </label>
            <input
                type="text"
                id="name"
                name="name"
                value="{{ old('name', $template->name) }}"
                required
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
        </div>

        {{-- Subject --}}
        <div>
            <label for="subject" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Email Subject Pattern <span class="text-error-500">*</span>
            </label>
            <input
                type="text"
                id="subject"
                name="subject"
                x-ref="subjectInput"
                value="{{ old('subject', $template->subject) }}"
                @focus="lastActiveField = 'subject'"
                required
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 font-mono text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            />
            <p class="mt-1 text-xs text-gray-500">Supports variable tokens, e.g. <code>&#123;&#123; company_name &#125;&#125;</code>, <code>&#123;&#123; title &#125;&#125;</code></p>
        </div>

        {{-- Body Editor Tab Switcher --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between border-b border-gray-200 pb-3 dark:border-gray-800">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Email Body Content <span class="text-error-500">*</span>
                </label>

                {{-- Segmented Tabs --}}
                <div class="inline-flex rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                    <button
                        type="button"
                        @click="bodyTab = 'html'; lastActiveField = 'body_html'; if (quill) { const q = (typeof Alpine !== 'undefined' && Alpine.raw) ? Alpine.raw(quill) : quill; $nextTick(() => q.focus()); }"
                        :class="bodyTab === 'html' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition cursor-pointer"
                    >
                        <span>✨</span>
                        <span>Rich HTML (Quill)</span>
                    </button>
                    <button
                        type="button"
                        @click="bodyTab = 'text'; lastActiveField = 'body_text'; $nextTick(() => { if ($refs.bodyTextInput) $refs.bodyTextInput.focus(); })"
                        :class="bodyTab === 'text' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-700 dark:text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
                        class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition cursor-pointer"
                    >
                        <span>📄</span>
                        <span>Plain Text Fallback</span>
                    </button>
                </div>
            </div>

            {{-- HTML Body via Quill Snow --}}
            <div x-show="bodyTab === 'html'" class="space-y-2">
                <div class="flex items-center justify-between">
                    <p class="text-xs text-gray-500">Rendered for standard rich email clients.</p>
                    <span class="text-xs text-gray-500">Click <strong>&lt;/&gt;</strong> in toolbar to edit raw HTML source</span>
                </div>

                <div
                    class="bg-white dark:bg-gray-900 rounded-lg overflow-hidden border border-gray-300 dark:border-gray-700"
                    @mousedown="lastActiveField = 'body_html'"
                    @click="lastActiveField = 'body_html'; const q = (typeof Alpine !== 'undefined' && Alpine.raw) ? Alpine.raw(quill) : quill; if (q) { q.focus(); }"
                >
                    <div x-ref="quillEditor" style="min-height: 260px;"></div>
                </div>
            </div>

            {{-- Plain Text Body (Optional) --}}
            <div x-show="bodyTab === 'text'" class="space-y-2">
                <p class="text-xs text-gray-500">Optional plain text fallback sent for text-only email clients.</p>
                <textarea
                    id="body_text"
                    name="body_text"
                    x-ref="bodyTextInput"
                    @focus="lastActiveField = 'body_text'"
                    rows="10"
                    placeholder="Enter plain text fallback content..."
                    class="w-full rounded-lg border border-gray-300 bg-transparent p-3 font-mono text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                >{{ old('body_text', $template->body_text) }}</textarea>
            </div>
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
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden cursor-pointer"
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
                        Template Live Draft Preview
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
                        Generating live preview with dummy data...
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

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('emailTemplateEditor', (config = {}) => ({
            quill: null,
            companyId: config.companyId || '',
            bodyTab: 'html',
            lastActiveField: 'body_html',
            lastSelectionIndex: 0,

            previewModalOpen: false,
            previewSubject: '',
            previewHtml: '',
            previewLoading: false,

            init() {
                this.initQuill();
            },

            initQuill() {
                const startQuill = () => {
                    if (typeof Quill === 'undefined') {
                        setTimeout(startQuill, 50);
                        return;
                    }

                    if (typeof htmlEditButton !== 'undefined' && !Quill.imports['modules/htmlEditButton']) {
                        try {
                            Quill.register('modules/htmlEditButton', htmlEditButton);
                        } catch (e) {}
                    }

                    const editorEl = this.$refs.quillEditor;
                    if (!editorEl || this.quill) return;

                    const quillInstance = new Quill(editorEl, {
                        theme: 'snow',
                        modules: {
                            toolbar: [
                                [{ 'header': [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'color': [] }, { 'background': [] }],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                [{ 'align': [] }],
                                ['link', 'clean']
                            ],
                            htmlEditButton: {
                                msg: 'Edit HTML source code',
                                okText: 'Apply Changes',
                                cancelText: 'Cancel',
                                buttonHTML: '&lt;/&gt;',
                                buttonTitle: 'Edit HTML Source Code'
                            }
                        }
                    });

                    this.quill = quillInstance;

                    const initialHtml = this.$refs.hiddenBodyHtml ? this.$refs.hiddenBodyHtml.value : '';
                    if (initialHtml) {
                        quillInstance.root.innerHTML = initialHtml;
                    }

                    quillInstance.on('selection-change', (range) => {
                        if (range) {
                            this.lastSelectionIndex = range.index;
                            this.lastActiveField = 'body_html';
                        }
                    });

                    quillInstance.on('text-change', () => {
                        if (this.$refs.hiddenBodyHtml) {
                            this.$refs.hiddenBodyHtml.value = quillInstance.getSemanticHTML ? quillInstance.getSemanticHTML() : quillInstance.root.innerHTML;
                        }
                    });

                    editorEl.addEventListener('focusin', () => {
                        this.lastActiveField = 'body_html';
                    });
                    editorEl.addEventListener('mousedown', () => {
                        this.lastActiveField = 'body_html';
                    });
                };

                this.$nextTick(() => {
                    startQuill();
                });
            },

            insertToken(token) {
                const tokenString = '{' + '{ ' + token + ' }}';

                if (this.lastActiveField === 'subject') {
                    const input = this.$refs.subjectInput;
                    if (input) {
                        const start = input.selectionStart || 0;
                        const end = input.selectionEnd || 0;
                        input.value = input.value.substring(0, start) + tokenString + input.value.substring(end);
                        input.focus();
                        input.selectionStart = input.selectionEnd = start + tokenString.length;
                    }
                    return;
                }

                if (this.bodyTab === 'text') {
                    const textarea = this.$refs.bodyTextInput;
                    if (textarea) {
                        const start = textarea.selectionStart || 0;
                        const end = textarea.selectionEnd || 0;
                        textarea.value = textarea.value.substring(0, start) + tokenString + textarea.value.substring(end);
                        textarea.focus();
                        textarea.selectionStart = textarea.selectionEnd = start + tokenString.length;
                    }
                    return;
                }

                // Targeting Rich HTML Editor (Quill)
                const q = (typeof Alpine !== 'undefined' && Alpine.raw) ? Alpine.raw(this.quill) : this.quill;
                if (q) {
                    q.focus();
                    const sel = q.getSelection();
                    const index = sel ? sel.index : ((typeof this.lastSelectionIndex === 'number') ? this.lastSelectionIndex : Math.max(0, q.getLength() - 1));

                    q.insertText(index, tokenString, 'user');
                    q.setSelection(index + tokenString.length, 0);
                    this.lastSelectionIndex = index + tokenString.length;

                    if (this.$refs.hiddenBodyHtml) {
                        this.$refs.hiddenBodyHtml.value = q.getSemanticHTML ? q.getSemanticHTML() : q.root.innerHTML;
                    }
                }
            },

            async generateLivePreview() {
                this.previewModalOpen = true;
                this.previewLoading = true;
                const currentSubject = this.$refs.subjectInput ? this.$refs.subjectInput.value : '';
                const q = (typeof Alpine !== 'undefined' && Alpine.raw) ? Alpine.raw(this.quill) : this.quill;
                const currentHtml = q
                    ? (q.getSemanticHTML ? q.getSemanticHTML() : q.root.innerHTML)
                    : (this.$refs.hiddenBodyHtml ? this.$refs.hiddenBodyHtml.value : '');

                try {
                    const res = await fetch(config.previewDraftRoute, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': config.csrfToken
                        },
                        body: JSON.stringify({
                            subject: currentSubject,
                            body_html: currentHtml,
                            company_id: this.companyId || null
                        })
                    });
                    const data = await res.json();
                    this.previewSubject = data.subject;
                    this.previewHtml = data.html;
                } catch (e) {
                    this.previewSubject = 'Error';
                    this.previewHtml = '<p class="text-error-500">Failed to generate live preview.</p>';
                } finally {
                    this.previewLoading = false;
                }
            },

            syncAndSubmit() {
                const q = (typeof Alpine !== 'undefined' && Alpine.raw) ? Alpine.raw(this.quill) : this.quill;
                if (q && this.$refs.hiddenBodyHtml) {
                    this.$refs.hiddenBodyHtml.value = q.getSemanticHTML ? q.getSemanticHTML() : q.root.innerHTML;
                }
            }
        }));
    });
</script>
@endsection
