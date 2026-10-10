<x-layouts.app title="Notifications">
    <div class="space-y-6">
        <x-page-heading title="Notifications" description="Inventory warnings and important administrative stock activity.">
            @if(auth()->user()->unreadNotifications()->exists())
                <x-slot:actions>
                    <form method="POST" action="{{ route('admin.notifications.read-all') }}">@csrf @method('PATCH')<x-button type="submit" variant="secondary">Mark all as read</x-button></form>
                </x-slot:actions>
            @endif
        </x-page-heading>

        @if($notifications->isEmpty())
            <x-empty-state title="No notifications" description="Stock warnings and important inventory activities will appear here." />
        @else
            <div class="space-y-3">
                @foreach($notifications as $notification)
                    @php($type = \App\Enums\AdminNotificationType::tryFrom($notification->data['type'] ?? '') ?? \App\Enums\AdminNotificationType::Activity)
                    <article class="rounded-2xl border p-4 sm:p-5 {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-brand-200 bg-brand-50/60 shadow-sm' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <x-status-badge :variant="$type->badgeVariant()">{{ $type->label() }}</x-status-badge>
                                    @unless($notification->read_at)<span class="inline-flex items-center gap-1 text-xs font-bold text-brand-800"><span class="size-2 rounded-full bg-brand-600"></span>Unread</span>@endunless
                                </div>
                                <h2 class="mt-3 text-base font-bold text-slate-950">{{ $notification->data['title'] ?? 'Notification' }}</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->data['description'] ?? '' }}</p>
                                <p class="mt-2 text-xs text-slate-500">{{ $formatter->transactionTimestamp($notification->created_at) }}</p>
                            </div>
                            <div class="flex shrink-0 flex-wrap gap-2">
                                @if($notification->data['related_url'] ?? null)<x-button href="{{ $notification->data['related_url'] }}" variant="secondary">View record</x-button>@endif
                                @unless($notification->read_at)<form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">@csrf @method('PATCH')<x-button type="submit">Mark as read</x-button></form>@endunless
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <x-pagination :paginator="$notifications" />
        @endif
    </div>
</x-layouts.app>
