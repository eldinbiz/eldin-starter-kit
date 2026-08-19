@foreach($rows as $template)
    <tr class="{{ $template->trashed() ? 'opacity-60' : '' }}">
        <td class="py-3.5">
            <div class="flex items-center gap-3">
                <span class="h-9 w-9 rounded-full bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-xs dark:bg-brand-500/10 dark:text-brand-400">
                    {{ strtoupper(substr($template->name, 0, 2)) }}
                </span>
                <div>
                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $template->name }}</p>
                    @if ($template->description)
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($template->description, 60) }}</p>
                    @endif
                </div>
            </div>
        </td>
        <td class="py-3.5">
            <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                {{ $template->approvable_type ? class_basename($template->approvable_type) : 'Any' }}
            </p>
        </td>
        <td class="py-3.5">
            <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                {{ $template->groups_count ?? $template->groups->count() }}
            </p>
        </td>
        <td class="py-3.5">
            @if ($template->trashed())
                <span class="inline-flex items-center gap-1 rounded-full bg-error-50 px-2.5 py-0.5 text-xs font-medium text-error-600 dark:bg-error-500/10 dark:text-error-400">
                    Deleted
                </span>
            @elseif (! $template->is_active)
                <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-0.5 text-xs font-medium text-warning-600 dark:bg-warning-500/10 dark:text-warning-400">
                    Disabled
                </span>
            @else
                <span class="inline-flex items-center gap-1 rounded-full bg-success-50 px-2.5 py-0.5 text-xs font-medium text-success-600 dark:bg-success-500/10 dark:text-success-500">
                    Active
                </span>
            @endif
        </td>
        <td class="py-3.5">
            <p class="text-gray-500 text-theme-sm dark:text-gray-400">@formatDate($template->created_at)</p>
        </td>
        <td class="py-3.5 text-right">
            <div class="flex items-center justify-end gap-2">
                @if ($template->trashed())
                    {{-- Trashed: Show restore button --}}
                    <form action="{{ route('admin.approval-workflows.restore', $template->id) }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer"
                            title="Restore and enable template"
                        >
                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M12 5V1L7 6l5 5V7c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6H4c0 4.42 3.58 8 8 8s8-3.58 8-8-3.58-8-8-8z"/>
                            </svg>
                        </button>
                    </form>

                    <button
                        type="button"
                        @click="$dispatch('open-delete-template', {
                            title: 'Permanently delete?',
                            message: 'This action cannot be undone. ' + @js($template->name) + ' will be permanently deleted.',
                            templateName: @js($template->name),
                            actionUrl: @js(route('admin.approval-workflows.soft-delete', $template->id))
                        })"
                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                        title="Permanently delete"
                    >
                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                    </button>
                @elseif (! $template->is_active)
                    {{-- Disabled: Show edit, enable, and delete buttons --}}
                    <a
                        href="{{ route('admin.approval-workflows.edit', $template->id) }}"
                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                    >
                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                        </svg>
                    </a>

                    <form action="{{ route('admin.approval-workflows.restore', $template->id) }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="text-gray-500 hover:text-success-500 dark:text-gray-400 dark:hover:text-success-400 cursor-pointer"
                            title="Enable template"
                        >
                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                            </svg>
                        </button>
                    </form>

                    <button
                        type="button"
                        @click="$dispatch('open-delete-template', {
                            title: 'Delete template?',
                            message: 'This will move ' + @js($template->name) + ' to the trash.',
                            templateName: @js($template->name),
                            actionUrl: @js(route('admin.approval-workflows.trash', $template->id))
                        })"
                        class="text-gray-500 hover:text-error-500 dark:text-gray-400 dark:hover:text-error-400 cursor-pointer"
                        title="Delete template"
                    >
                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
                        </svg>
                    </button>
                @else
                    {{-- Active: Show edit and disable buttons --}}
                    <a
                        href="{{ route('admin.approval-workflows.edit', $template->id) }}"
                        class="text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-white"
                    >
                        <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M5 19h1.42l9.4-9.4-1.42-1.42L5 17.58V19zm-1 1v-3.08L15.42 5.5a1 1 0 0 1 1.42 0l1.66 1.66a1 1 0 0 1 0 1.42L6.08 20H4zm13-13.58l1.42-1.42-1.42-1.42-1.42 1.42 1.42 1.42z"/>
                        </svg>
                    </a>

                    <form action="{{ route('admin.approval-workflows.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to disable this template?');">
                        @csrf
                        @method('DELETE')
                        <button
                            type="submit"
                            class="text-gray-500 hover:text-warning-500 dark:text-gray-400 dark:hover:text-warning-400 cursor-pointer"
                            title="Disable template"
                        >
                            <svg class="fill-current" width="18" height="18" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm5 11H7v-2h10v2z"/>
                            </svg>
                        </button>
                    </form>
                @endif
            </div>
        </td>
    </tr>
@endforeach

{{-- Reusable Confirmation Modal --}}
<tr x-data="{
    open: false,
    title: '',
    message: '',
    templateName: '',
    actionUrl: '',
    typedName: '',
    openModal(detail) {
        this.title = detail.title;
        this.message = detail.message;
        this.templateName = detail.templateName;
        this.actionUrl = detail.actionUrl;
        this.typedName = '';
        this.open = true;
    }
}" @open-delete-template.window="openModal($event.detail)">
    <td colspan="6" class="p-0 border-0">
        <div
            x-show="open"
            x-cloak
            x-trap.noscroll="open"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm"
        >
            <div
                @click.outside="open = false; typedName = ''"
                class="mx-4 w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-xl dark:border-gray-700 dark:bg-gray-800"
            >
                <p class="text-center text-sm font-medium text-gray-800 dark:text-white/90 mb-1" x-text="title"></p>
                <p class="text-center text-sm text-gray-500 dark:text-gray-400 mb-4" x-text="message"></p>
                <p class="text-center text-xs text-gray-500 dark:text-gray-400 mb-3">
                    Type <code class="rounded bg-gray-100 px-1 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-700 dark:text-gray-300" x-text="templateName"></code> to confirm.
                </p>

                <form :action="actionUrl" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="text" x-model="typedName" placeholder="Type template name"
                        class="mb-4 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 placeholder-gray-400 focus:border-error-500 focus:outline-none focus:ring-1 focus:ring-error-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:placeholder-gray-500">
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="open = false; typedName = ''"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                            :disabled="typedName !== templateName"
                            :class="typedName === templateName ? 'bg-error-500 hover:bg-error-600 cursor-pointer' : 'bg-gray-300 cursor-not-allowed dark:bg-gray-700'"
                            class="inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium text-white transition-colors">
                            Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </td>
</tr>
