@extends('admin.settings.layout', [
    'title' => 'Email Notification Templates',
    'heading' => 'Email Notification Templates',
    'subheading' => 'Customize notification email subjects and HTML bodies for approval requests, decisions, and system alerts.',
])

@section('settings-content')
<div x-data="{
    previewModalOpen: false,
    previewSubject: '',
    previewHtml: '',
    previewLoading: false,

    testModalOpen: false,
    testAction: '',
    testTemplateName: '',
    testEmail: '',
    testSending: false,
    testStatus: null,
    testMessage: '',

    async openPreview(templateId) {
        this.previewModalOpen = true;
        this.previewLoading = true;
        this.previewSubject = '';
        this.previewHtml = '';

        try {
            const res = await fetch(`/admin/settings/email-templates/${templateId}/preview`);
            const data = await res.json();
            this.previewSubject = data.subject;
            this.previewHtml = data.html;
        } catch (e) {
            this.previewSubject = 'Error';
            this.previewHtml = '<p class=&quot;text-error-500&quot;>Failed to generate template preview.</p>';
        } finally {
            this.previewLoading = false;
        }
    },

    openTest(templateId, templateName) {
        this.testAction = `/admin/settings/email-templates/${templateId}/test`;
        this.testTemplateName = templateName;
        this.testEmail = '';
        this.testStatus = null;
        this.testMessage = '';
        this.testModalOpen = true;
    },

    async dispatchTest() {
        if (!this.testEmail) return;
        this.testSending = true;
        this.testStatus = null;

        try {
            const res = await fetch(this.testAction, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                },
                body: JSON.stringify({ recipient_email: this.testEmail })
            });
            const data = await res.json();
            this.testStatus = res.ok ? 'success' : 'error';
            this.testMessage = data.message || 'Operation completed.';
        } catch (e) {
            this.testStatus = 'error';
            this.testMessage = 'Network error while dispatching test email.';
        } finally {
            this.testSending = false;
        }
    }
}">
    {{-- Scope Selector Filter --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.settings.email-templates.index') }}" class="flex items-center gap-3">
            <label for="company_filter" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                Scope Filter:
            </label>
            <select
                id="company_filter"
                name="company_id"
                onchange="this.form.submit()"
                class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
            >
                <option value="">Global System Templates</option>
                @foreach ($companies as $comp)
                    <option value="{{ $comp->id }}" {{ $selectedCompanyId === $comp->id ? 'selected' : '' }}>
                        {{ $comp->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Templates Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                <tr>
                    <th scope="col" class="py-3.5 px-4">Trigger Event</th>
                    <th scope="col" class="py-3.5 px-4">Template Name</th>
                    <th scope="col" class="py-3.5 px-4">Subject Pattern</th>
                    <th scope="col" class="py-3.5 px-4">Scope</th>
                    <th scope="col" class="py-3.5 px-4">Status</th>
                    <th scope="col" class="py-3.5 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($templates as $tpl)
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02]">
                        <td class="py-3.5 px-4 font-mono text-xs font-semibold text-gray-900 dark:text-white">
                            {{ $tpl->event }}
                        </td>
                        <td class="py-3.5 px-4 text-xs font-medium text-gray-800 dark:text-gray-200">
                            {{ $tpl->name }}
                        </td>
                        <td class="py-3.5 px-4 max-w-xs truncate text-xs text-gray-500 dark:text-gray-400 font-mono">
                            {{ $tpl->subject }}
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            @if ($tpl->company)
                                <span class="text-brand-600 dark:text-brand-400 font-medium">{{ $tpl->company->name }}</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">System Default</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            @if ($tpl->is_active)
                                <span class="inline-flex items-center rounded-md bg-success-50 px-2 py-1 text-xs font-medium text-success-700 ring-1 ring-success-700/10 dark:bg-success-500/10 dark:text-success-400">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400">
                                    Disabled
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    type="button"
                                    @click="openPreview({{ $tpl->id }})"
                                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="Live Preview"
                                >
                                    👁️ Preview
                                </button>

                                <button
                                    type="button"
                                    @click="openTest({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')"
                                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="Send Test"
                                >
                                    ✉️ Test
                                </button>

                                <a
                                    href="{{ route('admin.settings.email-templates.edit', $tpl->id) }}"
                                    class="inline-flex items-center gap-1 rounded bg-brand-50 px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20"
                                    title="Edit Template"
                                >
                                    ✏️ Edit
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No email templates found. Seed the database or create template records.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

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

    {{-- Test Email Dispatch Modal --}}
    <div
        x-show="testModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="testModalOpen = false"
    >
        <div
            @click.outside="testModalOpen = false"
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="mb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white">
                    Send Template Test Email
                </h4>
                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="`Template: ${testTemplateName}`"></p>
            </div>

            <div
                x-show="testStatus === 'success'"
                x-cloak
                class="mb-4 rounded-lg bg-success-50 p-3 text-xs text-success-600 dark:bg-success-500/10 dark:text-success-400"
                x-text="testMessage"
            ></div>

            <div
                x-show="testStatus === 'error'"
                x-cloak
                class="mb-4 rounded-lg bg-error-50 p-3 text-xs text-error-600 dark:bg-error-500/10 dark:text-error-400"
                x-text="testMessage"
            ></div>

            <div class="space-y-4">
                <div>
                    <label for="template_test_email" class="mb-1.5 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        Recipient Email Address
                    </label>
                    <input
                        type="email"
                        id="template_test_email"
                        x-model="testEmail"
                        placeholder="you@example.com"
                        class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-950 dark:text-white/90"
                    />
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    @click="testModalOpen = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    Close
                </button>
                <button
                    type="button"
                    @click="dispatchTest()"
                    :disabled="!testEmail || testSending"
                    class="inline-flex items-center gap-2 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white hover:bg-brand-600 disabled:opacity-50"
                >
                    <span x-show="testSending" class="animate-spin">⏳</span>
                    <span x-text="testSending ? 'Sending...' : 'Send Test Mail'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
