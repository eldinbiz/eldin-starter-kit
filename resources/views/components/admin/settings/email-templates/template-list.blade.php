<?php

use App\Actions\EmailTemplates\DeleteEmailTemplateAction;
use App\Models\Company;
use App\Models\EmailTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

new class extends Component
{
    #[Url(as: 'company_id')]
    public ?int $selectedCompanyId = null;

    #[Url(as: 'search')]
    public string $search = '';

    public ?int $templateToDeleteId = null;

    public ?string $templateToDeleteName = null;

    public ?string $flashSuccess = null;

    public ?string $flashError = null;

    public function mount(?int $selectedCompanyId = null, string $search = ''): void
    {
        if ($selectedCompanyId !== null) {
            $this->selectedCompanyId = $selectedCompanyId;
        } elseif (request()->filled('company_id')) {
            $this->selectedCompanyId = (int) request()->query('company_id');
        }

        if ($search !== '') {
            $this->search = $search;
        } elseif (request()->filled('search')) {
            $this->search = trim((string) request()->query('search'));
        } elseif (request()->filled('q')) {
            $this->search = trim((string) request()->query('q'));
        }
    }

    public function promptDelete(int $id): void
    {
        $template = EmailTemplate::find($id);
        if ($template) {
            $this->templateToDeleteId = $template->id;
            $this->templateToDeleteName = $template->name;
        }
    }

    public function cancelDelete(): void
    {
        $this->templateToDeleteId = null;
        $this->templateToDeleteName = null;
    }

    public function deleteTemplate(DeleteEmailTemplateAction $action): void
    {
        $this->authorize('manage-system-settings');

        if ($this->templateToDeleteId) {
            $name = $this->templateToDeleteName;
            $template = EmailTemplate::findOrFail($this->templateToDeleteId);
            $action->execute($template);
            $this->cancelDelete();
            $this->flashSuccess = "Email template '{$name}' was deleted successfully.";
        }
    }

    public function dismissFlash(): void
    {
        $this->flashSuccess = null;
        $this->flashError = null;
    }

    #[Computed]
    public function companies(): Collection
    {
        return Company::where('is_active', true)->orderBy('name')->get();
    }

    #[Computed]
    public function templates(): Collection
    {
        $query = EmailTemplate::query();

        if ($this->selectedCompanyId !== null) {
            $query->where(function (Builder $q): void {
                $q->where('company_id', $this->selectedCompanyId)->orWhereNull('company_id');
            });
        } elseif ($this->search === '') {
            $query->whereNull('company_id');
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function (Builder $q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('event', 'like', "%{$term}%")
                    ->orWhere('subject', 'like', "%{$term}%");
            });
        }

        return $query->with('company')->orderBy('event')->get();
    }

    #[Computed]
    public function companyOverrideEvents(): array
    {
        if ($this->selectedCompanyId === null) {
            return [];
        }

        return EmailTemplate::where('company_id', $this->selectedCompanyId)->pluck('event')->toArray();
    }
};
?>

<div>
    {{-- Flash Notifications --}}
    @if ($flashSuccess)
        <div class="mb-6 rounded-xl border border-success-200 bg-success-50 p-4 text-sm text-success-700 dark:border-success-800/30 dark:bg-success-500/10 dark:text-success-400 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <span>{{ $flashSuccess }}</span>
            </div>
            <button type="button" wire:click="dismissFlash" class="text-success-600 hover:text-success-800 dark:text-success-400 dark:hover:text-success-200 cursor-pointer">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
    @endif

    {{-- Scope & Search Controls Bar --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="company_filter" class="text-xs font-medium text-gray-500 dark:text-gray-400">
                    Scope:
                </label>
                <select
                    id="company_filter"
                    wire:model.live="selectedCompanyId"
                    class="h-9.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                >
                    <option value="">Global System Defaults</option>
                    @foreach ($this->companies as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->name }} (Overrides)
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="relative flex-1 sm:w-64">
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search templates..."
                    class="h-9.5 w-full rounded-lg border border-gray-300 bg-white pl-8 pr-3 text-xs text-gray-800 shadow-theme-xs focus:border-brand-500 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                />
                <span class="absolute inset-y-0 left-0 flex items-center pl-2.5 pointer-events-none text-gray-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </span>
            </div>
        </div>

        <a
            href="{{ route('admin.settings.email-templates.create') }}"
            wire:navigate
            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-brand-500 px-4 py-2 text-xs font-medium text-white shadow-theme-xs hover:bg-brand-600 focus:outline-hidden cursor-pointer"
        >
            <span>+</span>
            <span>Create New Template</span>
        </a>
    </div>

    {{-- Templates Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
        <table class="w-full text-left text-sm text-gray-700 dark:text-gray-300">
            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-gray-900/50 dark:text-gray-400 border-b border-gray-200 dark:border-gray-800">
                <tr>
                    <th scope="col" class="py-3 px-4">Template & Event Key</th>
                    <th scope="col" class="py-3 px-4">Subject Preview</th>
                    <th scope="col" class="py-3 px-4">Scope</th>
                    <th scope="col" class="py-3 px-4">Status</th>
                    <th scope="col" class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-transparent">
                @forelse ($this->templates as $template)
                    @php
                        $isCompanyOverride = $template->company_id !== null;
                        $hasOverrideAlready = in_array($template->event, $this->companyOverrideEvents, true);
                    @endphp
                    <tr class="hover:bg-gray-50/50 dark:hover:bg-white/[0.02] transition-colors">
                        <td class="py-3.5 px-4">
                            <div class="font-medium text-xs text-gray-900 dark:text-white">
                                {{ $template->name }}
                            </div>
                            <div class="font-mono text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                                {{ $template->event }}
                            </div>
                        </td>
                        <td class="py-3.5 px-4 max-w-sm truncate text-xs text-gray-600 dark:text-gray-300">
                            {{ $template->subject }}
                        </td>
                        <td class="py-3.5 px-4 text-xs whitespace-nowrap">
                            @if ($isCompanyOverride && $template->company)
                                <span class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-2 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-500/15 dark:text-purple-400 border border-purple-200 dark:border-purple-500/20">
                                    🏢 {{ $template->company->name }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    🌐 Global Default
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 whitespace-nowrap">
                            @if ($template->is_active)
                                <span class="inline-flex items-center rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-500/10 dark:text-success-400">
                                    Active
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    Disabled
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right whitespace-nowrap">
                            <div class="flex items-center justify-end gap-2">
                                {{-- Quick Override Button if on company scope --}}
                                @if ($selectedCompanyId !== null && ! $isCompanyOverride && ! $hasOverrideAlready)
                                    <a
                                        href="{{ route('admin.settings.email-templates.create', ['event' => $template->event, 'company_id' => $selectedCompanyId]) }}"
                                        wire:navigate
                                        title="Create company override"
                                        class="inline-flex items-center gap-1 rounded-md border border-purple-200 bg-purple-50 px-2 py-1 text-[11px] font-medium text-purple-700 hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/30 dark:text-purple-300"
                                    >
                                        + Override
                                    </a>
                                @endif

                                {{-- Edit Button with wire:navigate --}}
                                <a
                                    href="{{ route('admin.settings.email-templates.edit', $template->id) }}"
                                    wire:navigate
                                    title="Edit template"
                                    class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white transition"
                                >
                                    <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                                    </svg>
                                </a>

                                {{-- Delete Button --}}
                                <button
                                    type="button"
                                    wire:click="promptDelete({{ $template->id }})"
                                    title="{{ $isCompanyOverride ? 'Reset company override' : 'Delete template' }}"
                                    class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer transition"
                                >
                                    <svg class="fill-current" width="16" height="16" viewBox="0 0 24 24">
                                        <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 text-center text-xs text-gray-500 dark:text-gray-400">
                            No email templates found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Delete / Reset Override Modal --}}
    @if ($templateToDeleteId)
        <div
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 backdrop-blur-sm"
            x-data
            x-trap.noscroll="true"
        >
            <div
                @click.outside="$wire.cancelDelete()"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-error-50 dark:bg-error-500/10 mb-4">
                    <svg class="h-7 w-7 text-error-600 dark:text-error-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                    </svg>
                </div>

                <h3 class="text-center text-lg font-semibold text-gray-800 dark:text-white/90 mb-2">
                    Delete Email Template?
                </h3>

                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Are you sure you want to delete template <strong>{{ $templateToDeleteName }}</strong>?
                </p>

                <div class="flex items-center justify-end gap-3">
                    <button
                        type="button"
                        wire:click="cancelDelete"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 cursor-pointer"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        wire:click="deleteTemplate"
                        class="rounded-lg bg-error-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-error-700 shadow-theme-xs cursor-pointer"
                    >
                        Delete Template
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
