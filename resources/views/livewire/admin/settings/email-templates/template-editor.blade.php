{{-- Quill Editor Styles & Scripts via CDN --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill-html-edit-button@2.2.7/dist/quill.htmlEditButton.min.js"></script>

<div
    x-data="{
        content: @entangle('body_html'),
        quill: null,
        init() {
            if (typeof Quill !== 'undefined') {
                this.quill = new Quill(this.$refs.editor, {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ header: [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ color: [] }, { background: [] }],
                            [{ list: 'ordered' }, { list: 'bullet' }],
                            ['link', 'clean']
                        ]
                    }
                });
                this.quill.root.innerHTML = this.content || '';
                this.quill.on('text-change', () => {
                    this.content = this.quill.root.innerHTML;
                });
            }
        },
        insertToken(tokenName) {
            const token = '\{\{ ' + tokenName + ' \}\}';
            if (this.quill) {
                const range = this.quill.getSelection(true);
                const pos = range ? range.index : this.quill.getLength() - 1;
                this.quill.insertText(pos, token);
                this.content = this.quill.root.innerHTML;
            } else {
                this.content += token;
            }
        }
    }"
    class="space-y-6"
>
    {{-- Header & Quick Actions --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between pb-4 border-b border-gray-200 dark:border-gray-800">
        <div>
            <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                {{ $templateId ? "Edit Email Template: {$name}" : 'Create Email Template' }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                Configure template attributes, rich HTML body, dynamic variable placeholders, and send test emails.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <button
                type="button"
                wire:click="openPreview"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer shadow-theme-xs transition"
            >
                <span>👁️ Live Preview</span>
            </button>

            @if ($templateId)
                <button
                    type="button"
                    wire:click="openTestModal"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer shadow-theme-xs transition"
                >
                    <span>✉️ Send Test</span>
                </button>
            @endif

            <a
                href="{{ route('admin.settings.email-templates.index') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3.5 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer shadow-theme-xs transition"
            >
                ← Back to Templates
            </a>
        </div>
    </div>

    {{-- Editor Form --}}
    <form wire:submit="save" class="space-y-6">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            {{-- Template Name --}}
            <div>
                <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Template Display Name <span class="text-error-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    wire:model="name"
                    placeholder="e.g. Purchase Request Submitted"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                @error('name') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>

            {{-- Event Key --}}
            <div>
                <label for="event" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Event Trigger Key <span class="text-error-500">*</span>
                </label>
                <input
                    type="text"
                    id="event"
                    wire:model="event"
                    placeholder="e.g. pr_submitted, approval_decision"
                    class="h-11 w-full font-mono rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                @error('event') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>

            {{-- Target Scope (Company / Global) --}}
            <div>
                <label for="company_id" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Template Scope
                </label>
                <select
                    id="company_id"
                    wire:model="company_id"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    <option value="">🌐 Global Default (All Companies)</option>
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">🏢 {{ $comp->name }} (Override)</option>
                    @endforeach
                </select>
            </div>

            {{-- Active Status Toggle --}}
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Status
                </label>
                <div class="flex items-center gap-3 pt-2">
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input
                            type="checkbox"
                            wire:model="is_active"
                            class="peer sr-only"
                        >
                        <div class="peer h-6 w-11 rounded-full bg-gray-200 after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-gray-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-brand-500 peer-checked:after:translate-x-full peer-checked:after:border-white dark:bg-gray-700"></div>
                    </label>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $is_active ? 'Template is active & dispatchable' : 'Template is disabled' }}
                    </span>
                </div>
            </div>

            {{-- Subject Line --}}
            <div class="md:col-span-2">
                <label for="subject" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                    Email Subject Line <span class="text-error-500">*</span>
                </label>
                <input
                    type="text"
                    id="subject"
                    wire:model="subject"
                    placeholder="e.g. Your Request #\{\{ title \}\} is now pending approval"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                @error('subject') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Dynamic Variable Insert Toolbar --}}
        <div class="rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/30">
            <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                    Placeholder Token Toolbar (Click to insert):
                </span>
                <div class="flex items-center gap-1">
                    <input
                        type="text"
                        wire:model="newVariable"
                        wire:keydown.enter.prevent="addVariable"
                        placeholder="Add custom placeholder..."
                        class="h-7 rounded border border-gray-300 bg-white px-2 text-xs text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    />
                    <button
                        type="button"
                        wire:click="addVariable"
                        class="h-7 px-2.5 rounded bg-gray-200 hover:bg-gray-300 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200 cursor-pointer"
                    >
                        +
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
                @foreach ($variables as $v)
                    <span class="inline-flex items-center gap-1 rounded-md bg-white border border-gray-200 px-2 py-1 text-xs font-mono text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 shadow-xs">
                        <button
                            type="button"
                            @click="insertToken('{{ $v }}')"
                            class="hover:text-brand-600 dark:hover:text-brand-400 cursor-pointer font-bold"
                            title="Insert token"
                        >
                            + &#123;&#123; {{ $v }} &#125;&#125;
                        </button>
                        <button
                            type="button"
                            wire:click="removeVariable('{{ $v }}')"
                            class="text-gray-400 hover:text-error-500 cursor-pointer ml-1"
                        >
                            ×
                        </button>
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Rich HTML (Quill) WYSIWYG Editor Container --}}
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Rich HTML (Quill) <span class="text-error-500">*</span>
            </label>
            <div wire:ignore class="rounded-xl border border-gray-300 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900">
                <div x-ref="editor" style="min-height: 240px;" class="text-sm text-gray-800 dark:text-gray-200"></div>
            </div>
            @error('body_html') <p class="mt-1 text-xs text-error-500">{{ $message }}</p> @enderror
        </div>

        {{-- Plain Text Fallback Container --}}
        <div>
            <label for="body_text" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Plain Text Fallback (Optional)
            </label>
            <textarea
                id="body_text"
                wire:model="body_text"
                rows="4"
                placeholder="Plain text email version for non-HTML mail clients..."
                class="w-full font-mono text-xs rounded-lg border border-gray-300 bg-white p-3 text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            ></textarea>
        </div>

        {{-- Save Action --}}
        <div class="flex items-center justify-end pt-4 border-t border-gray-200 dark:border-gray-800 gap-3">
            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-center text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden focus:ring-2 focus:ring-brand-500/50 cursor-pointer"
            >
                <span wire:loading.remove wire:target="save">Save Email Template</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>
    </form>

    {{-- Live Preview Modal --}}
    @if ($previewModalOpen)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.closePreview()"
                class="mx-4 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900 flex flex-col max-h-[85vh]"
            >
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                        📧 Template Preview (Simulated Data)
                    </h3>
                    <button type="button" wire:click="closePreview" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="overflow-y-auto flex-1 space-y-4">
                    <div class="rounded-lg bg-gray-50 p-3 text-xs dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700">
                        <span class="font-semibold text-gray-600 dark:text-gray-400">Subject:</span>
                        <span class="font-medium text-gray-900 dark:text-white ml-1">{{ $previewSubject }}</span>
                    </div>

                    <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800/30 prose dark:prose-invert max-w-none text-sm">
                        {!! $previewHtml !!}
                    </div>
                </div>

                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                    <button
                        type="button"
                        wire:click="closePreview"
                        class="rounded-lg bg-gray-800 px-4 py-2 text-xs font-medium text-white hover:bg-gray-900 dark:bg-gray-700 dark:hover:bg-gray-600 cursor-pointer"
                    >
                        Close Preview
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Send Test Email Modal --}}
    @if ($testModalOpen)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.closeTestModal()"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-white">
                        🚀 Send Test Template Email
                    </h3>
                    <button type="button" wire:click="closeTestModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 cursor-pointer">
                        ✕
                    </button>
                </div>

                @if ($testStatus === 'success')
                    <div class="mb-4 rounded-lg bg-success-50 p-3 text-xs text-success-700 dark:bg-success-500/10 dark:text-success-400">
                        ✓ {{ $testMessage }}
                    </div>
                @elseif ($testStatus === 'error')
                    <div class="mb-4 rounded-lg bg-error-50 p-3 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-400">
                        ⚠️ {{ $testMessage }}
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <label for="testEmail" class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Recipient Email Address
                        </label>
                        <input
                            type="email"
                            id="testEmail"
                            wire:model="testEmail"
                            placeholder="recipient@example.com"
                            class="h-9.5 w-full rounded-lg border border-gray-300 bg-white px-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button
                            type="button"
                            wire:click="closeTestModal"
                            class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            wire:click="sendTestEmail"
                            class="rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 shadow-theme-xs cursor-pointer"
                        >
                            <span wire:loading.remove wire:target="sendTestEmail">Send Test</span>
                            <span wire:loading wire:target="sendTestEmail">Sending...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
