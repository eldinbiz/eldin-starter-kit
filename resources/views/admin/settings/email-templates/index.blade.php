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

    deleteModalOpen: false,
    deleteAction: '',
    deleteTitle: '',
    deleteMessage: '',
    deleteIsOverride: false,

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
    },

    openDelete(templateId, templateName, isOverride) {
        this.deleteAction = `/admin/settings/email-templates/${templateId}`;
        this.deleteIsOverride = isOverride;
        if (isOverride) {
            this.deleteTitle = 'Reset Company Override?';
            this.deleteMessage = `Are you sure you want to reset the company override for '${templateName}'? The company will immediately fall back to the Global System Default template.`;
        } else {
            this.deleteTitle = 'Delete Email Template?';
            this.deleteMessage = `Are you sure you want to delete the email template '${templateName}'? This action cannot be undone.`;
        }
        this.deleteModalOpen = true;
    }
}">
    {{-- Scope & Search Controls Bar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('admin.settings.email-templates.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="company_filter" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Scope:
                </label>
                <select
                    id="company_filter"
                    name="company_id"
                    onchange="this.form.submit()"
                    class="h-9 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    <option value="">Global System Templates</option>
                    @foreach ($companies as $comp)
                        <option value="{{ $comp->id }}" {{ (string) $selectedCompanyId === (string) $comp->id ? 'selected' : '' }}>
                            🏢 {{ $comp->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <input
                    type="text"
                    name="search"
                    value="{{ $search ?? '' }}"
                    placeholder="Search templates..."
                    class="h-9 w-48 sm:w-60 rounded-lg border border-gray-300 bg-transparent px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs placeholder:text-gray-400 focus:border-brand-300 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                @if ($search)
                    <a
                        href="{{ route('admin.settings.email-templates.index', ['company_id' => $selectedCompanyId]) }}"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400"
                        title="Clear search"
                    >
                        ✕
                    </a>
                @endif
                <button
                    type="submit"
                    class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300"
                >
                    Filter
                </button>
            </div>
        </form>

        <a
            href="{{ route('admin.settings.email-templates.create', ['company_id' => $selectedCompanyId]) }}"
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden"
        >
            <span>+</span>
            <span>New Template</span>
        </a>
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
                    @php
                        $isCompanyOverride = $tpl->company_id !== null;
                        $hasOverrideForEvent = $selectedCompanyId && in_array($tpl->event, $companyOverrideEvents ?? []);
                    @endphp
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
                            @if ($isCompanyOverride)
                                <span class="inline-flex items-center gap-1 rounded-md bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 ring-1 ring-brand-700/10 dark:bg-brand-500/10 dark:text-brand-400">
                                    🏢 {{ $tpl->company->name ?? 'Company' }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-md bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-600/10 dark:bg-gray-400/10 dark:text-gray-400">
                                    🌐 Global Default
                                </span>
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
                            <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                {{-- Preview --}}
                                <button
                                    type="button"
                                    @click="openPreview({{ $tpl->id }})"
                                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="Live Preview"
                                >
                                    👁️ Preview
                                </button>

                                {{-- Test Email --}}
                                <button
                                    type="button"
                                    @click="openTest({{ $tpl->id }}, '{{ addslashes($tpl->name) }}')"
                                    class="inline-flex items-center gap-1 rounded bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
                                    title="Send Test Email"
                                >
                                    ✉️ Test
                                </button>

                                {{-- Edit --}}
                                <a
                                    href="{{ route('admin.settings.email-templates.edit', $tpl->id) }}"
                                    class="inline-flex items-center gap-1 rounded bg-brand-50 px-2 py-1 text-xs font-medium text-brand-600 hover:bg-brand-100 dark:bg-brand-500/10 dark:text-brand-400 dark:hover:bg-brand-500/20"
                                    title="Edit Template"
                                >
                                    ✏️ Edit
                                </a>

                                {{-- 1-Click Create Override (If viewing company and this row is global without existing override) --}}
                                @if ($selectedCompanyId && ! $isCompanyOverride && ! $hasOverrideForEvent)
                                    <a
                                        href="{{ route('admin.settings.email-templates.create', ['event' => $tpl->event, 'company_id' => $selectedCompanyId]) }}"
                                        class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-500/10 dark:text-indigo-400"
                                        title="Create company-specific override"
                                    >
                                        + Override
                                    </a>
                                @endif

                                {{-- Reset / Delete Action --}}
                                @if ($isCompanyOverride)
                                    <button
                                        type="button"
                                        @click="openDelete({{ $tpl->id }}, '{{ addslashes($tpl->name) }}', true)"
                                        class="inline-flex items-center gap-1 rounded bg-warning-50 px-2 py-1 text-xs font-medium text-warning-700 hover:bg-warning-100 dark:bg-warning-500/10 dark:text-warning-400"
                                        title="Reset company override to global default"
                                    >
                                        ↺ Reset
                                    </button>
                                @else
                                    <button
                                        type="button"
                                        @click="openDelete({{ $tpl->id }}, '{{ addslashes($tpl->name) }}', false)"
                                        class="inline-flex items-center gap-1 rounded bg-error-50 px-2 py-1 text-xs font-medium text-error-600 hover:bg-error-100 dark:bg-error-500/10 dark:text-error-400"
                                        title="Delete template"
                                    >
                                        🗑️
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-xs text-gray-500 dark:text-gray-400">
                            No email templates found matching your criteria.
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

    {{-- Delete / Reset Confirmation Modal (Skill: alpine-modal) --}}
    <div
        x-show="deleteModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-gray-900/50 backdrop-blur-xs"
        @keydown.escape.window="deleteModalOpen = false"
    >
        <div
            @click.outside="deleteModalOpen = false"
            class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xl dark:border-gray-800 dark:bg-gray-900"
        >
            <div class="mb-4">
                <h4 class="text-base font-semibold text-gray-900 dark:text-white" x-text="deleteTitle"></h4>
                <p class="mt-2 text-xs text-gray-600 dark:text-gray-400" x-text="deleteMessage"></p>
            </div>

            <form :action="deleteAction" method="POST" class="mt-6 flex justify-end gap-3">
                @csrf
                @method('DELETE')
                <button
                    type="button"
                    @click="deleteModalOpen = false"
                    class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-error-600 px-4 py-2 text-xs font-medium text-white hover:bg-error-700 focus:outline-hidden"
                    x-text="deleteIsOverride ? 'Reset to Default' : 'Confirm Delete'"
                ></button>
            </form>
        </div>
    </div>
</div>
@endsection
