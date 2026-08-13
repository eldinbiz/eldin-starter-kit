@extends('layouts.app')

@section('title', 'Notifications Center')

@section('content')
<div x-data="{ pageName: 'Notifications Center' }">
    @include('partials.breadcrumb')
</div>

<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
    {{-- Header Controls & Tabs --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between pb-5 border-b border-gray-100 dark:border-gray-800">
        {{-- Filter Tabs --}}
        <div class="flex items-center gap-2">
            <a
                href="{{ route('notifications.index', ['filter' => 'all']) }}"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'all' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}"
            >
                All
            </a>
            <a
                href="{{ route('notifications.index', ['filter' => 'unread']) }}"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'unread' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}"
            >
                Unread
            </a>
            <a
                href="{{ route('notifications.index', ['filter' => 'read']) }}"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors {{ $filter === 'read' ? 'bg-brand-500 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' }}"
            >
                Read
            </a>
        </div>

        {{-- Bulk Actions --}}
        @if(auth()->user()->unreadNotifications()->count() > 0)
        <form action="{{ route('notifications.read-all') }}" method="POST">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800"
            >
                <svg class="w-3.5 h-3.5 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Mark all as read
            </button>
        </form>
        @endif
    </div>

    {{-- Notification Cards List --}}
    <div class="divide-y divide-gray-100 dark:divide-gray-800 my-2">
        @forelse($notifications as $notification)
            @php
                $data = $notification->data;
                $title = $data['title'] ?? 'Notification';
                $message = $data['message'] ?? '';
                $type = $data['type'] ?? 'system';
                $actionText = $data['action_text'] ?? 'View Details';
                $companyName = $data['company_name'] ?? null;
                $level = $data['level'] ?? 'info';
                $isRead = !is_null($notification->read_at);

                $levelColor = match($level) {
                    'success' => 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500',
                    'warning' => 'bg-warning-50 text-warning-600 dark:bg-warning-500/15 dark:text-warning-500',
                    'danger'  => 'bg-error-50 text-error-600 dark:bg-error-500/15 dark:text-error-500',
                    default   => 'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-400',
                };
            @endphp
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 py-4 px-2 hover:bg-gray-50/50 dark:hover:bg-white/[0.01] rounded-xl transition-colors {{ !$isRead ? 'bg-brand-50/20 dark:bg-brand-500/5' : '' }}">
                <div class="flex items-start gap-3">
                    {{-- Read/Unread Dot --}}
                    <div class="mt-1 shrink-0">
                        @if(!$isRead)
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-brand-500 shadow-xs"></span>
                        @else
                            <span class="inline-block h-2.5 w-2.5 rounded-full bg-gray-200 dark:bg-gray-700"></span>
                        @endif
                    </div>

                    {{-- Content Details --}}
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                {{ $title }}
                            </h4>

                            @if($companyName)
                                <span class="rounded-md bg-gray-100 dark:bg-gray-800 px-2 py-0.5 text-[11px] font-semibold text-gray-600 dark:text-gray-300">
                                    {{ $companyName }}
                                </span>
                            @endif

                            <span class="rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $levelColor }}">
                                {{ $type }}
                            </span>
                        </div>

                        <p class="text-xs text-gray-600 dark:text-gray-400 max-w-2xl">
                            {{ $message }}
                        </p>

                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>

                {{-- Action Button --}}
                <div class="shrink-0 flex items-center gap-2 pl-5 sm:pl-0">
                    <form action="{{ route('notifications.mark-as-read', $notification->id) }}" method="POST">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center gap-1 text-xs font-semibold text-brand-500 hover:text-brand-600 hover:underline bg-brand-50/50 dark:bg-brand-500/10 px-3 py-1.5 rounded-lg transition-colors"
                        >
                            {{ $actionText }} &rarr;
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                No notifications found in this view.
            </div>
        @endforelse
    </div>

    {{-- Pagination Links --}}
    @if($notifications->hasPages())
    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
        {{ $notifications->links() }}
    </div>
    @endif
</div>
@endsection
