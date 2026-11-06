@extends('admin.layouts.admin')

@section('title', 'Photo moderation — '.config('app.name').' Admin')

@section('admin-body')
    <div class="mb-4 flex gap-2 border-b pb-3" style="border-color:var(--border-default)">
        <a href="{{ route('admin.photos.index') }}" class="rounded-md bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700">
            Profile Photos
        </a>
        <a href="{{ route('admin.jadhagam.index') }}" class="rounded-md px-3 py-1.5 text-xs font-medium hover:bg-primary-50" style="color:var(--text-muted)">
            Jadhagam (Horoscope) Photos
        </a>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Photo moderation</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Oldest first. A rejected photo is kept, never deleted — the member is told which one and why, and a repeat pattern is the evidence behind a later suspension.
            </p>
        </div>

        <nav class="flex rounded-md border" style="border-color:var(--border-default)" aria-label="Queue status">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                <a href="{{ route('admin.photos.index', ['status' => $value] + request()->only('gender')) }}"
                   @class([
                       'px-4 py-2 text-sm font-medium first:rounded-l-md last:rounded-r-md',
                       'bg-primary-50 text-primary-700' => $status === $value,
                   ])
                   @if ($status === $value) aria-current="page" @endif>
                    {{ $label }}
                    @if ($value === 'pending' && $pendingCount > 0)
                        <span class="ml-1 rounded-full bg-warning/15 px-1.5 py-0.5 text-xs text-warning">{{ $pendingCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    {{-- Rendered hidden and unhidden by resources/js/admin.js once the
         keyboard layer has actually attached. A hint advertising shortcuts
         that a blocked or broken script never bound is worse than no hint —
         the no-JS grid below is fully usable, and this line is the only thing
         on the page that would lie about it. --}}
    @if ($status === 'pending' && $photos->isNotEmpty())
        <p id="photo-queue-hints" hidden
           class="mt-4 rounded-md border px-3 py-2 text-xs"
           style="border-color:var(--border-default);color:var(--text-muted)">
            <kbd class="rounded border px-1">A</kbd> approve ·
            <kbd class="rounded border px-1">R</kbd> reject (with a reason picked) ·
            <kbd class="rounded border px-1">←</kbd> <kbd class="rounded border px-1">→</kbd> move ·
            <kbd class="rounded border px-1">U</kbd> undo ·
            <kbd class="rounded border px-1">Esc</kbd> undo everything still pending.
            Decisions are held for {{ (int) config('matrimony.moderation.undo_seconds', 6) }}s before they are sent.
        </p>
    @endif

    {{-- The batch form is empty and lives outside the grid on purpose: the
         checkboxes below associate with it by id (`form="photo-bulk"`), which
         is what lets each card keep its own approve/reject forms without
         nesting one <form> inside another. See <x-admin.bulk-bar>. --}}
    @if ($status === 'pending' && $photos->isNotEmpty())
        <form id="photo-bulk" method="POST" action="{{ route('admin.photos.bulk') }}">@csrf</form>

        <div class="mt-4">
            <x-admin.bulk-bar form="photo-bulk" label="selected" :reasons="$reasons" />
        </div>
    @endif

    @if ($photos->isEmpty())
        <p class="mt-6 rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
            @if ($status === 'pending')
                Nothing waiting. The queue is clear.
            @else
                No {{ $status }} photos.
            @endif
        </p>
    @else
        {{-- `data-photo-queue` is what admin.js keys off, and it is only
             emitted for the pending queue: approved and rejected are archive
             views with no decision to make, so binding A/R there would offer
             a shortcut for a route that refuses the write anyway. --}}
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
             @if ($status === 'pending')
                 data-photo-queue
                 data-undo-seconds="{{ (int) config('matrimony.moderation.undo_seconds', 6) }}"
             @endif>
            @foreach ($photos as $photo)
                <article class="card overflow-hidden"
                         @if ($status === 'pending')
                             data-photo-card
                             data-photo-id="{{ $photo->id }}"
                             data-photo-label="{{ $photo->user?->profile_code }}"
                             data-approve-url="{{ route('admin.photos.approve', $photo) }}"
                             data-reject-url="{{ route('admin.photos.reject', $photo) }}"
                             data-prefetch-url="{{ $photo->urlFor('original') }}"
                         @endif>
                    {{-- The full-size file, not the thumbnail: a moderator
                         decides on detail a 200px derivative hides, and a
                         watermark or a second face in the frame is exactly
                         what the thumbnail loses. --}}
                    <a href="{{ $photo->urlFor('original') }}" target="_blank" rel="noopener">
                        <img src="{{ $photo->urlFor('thumbnail') }}" alt="" loading="lazy" class="aspect-square w-full object-cover">
                    </a>

                    <div class="space-y-2 p-3 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.show', $photo->user_id) }}" class="block truncate font-medium text-primary-600 hover:underline">
                                    {{ $photo->user?->name }}
                                </a>
                                <p class="truncate font-mono text-xs" style="color:var(--text-muted)">{{ $photo->user?->profile_code }}</p>
                            </div>

                            @if ($status === 'pending')
                                <input type="checkbox" name="ids[]" value="{{ $photo->id }}" form="photo-bulk"
                                       aria-label="Select photo from {{ $photo->user?->profile_code }}"
                                       class="mt-0.5 rounded border" style="border-color:var(--border-default)">
                            @endif
                        </div>

                        <p class="text-xs" style="color:var(--text-muted)">
                            Uploaded {{ $photo->created_at->diffForHumans(short: true) }}
                            @if ($photo->is_primary) · <span class="font-medium">Primary</span> @endif
                        </p>

                        @if ($status === 'pending')
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.photos.approve', $photo) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-success px-3 py-1.5 text-xs font-medium text-white hover:opacity-90">
                                        Approve
                                    </button>
                                </form>
                            </div>

                            {{-- One reason picker per card rather than a
                                 shared dialog: with no JS a shared one cannot
                                 know which photo it was opened for, and the
                                 reason is mandatory on every rejection. --}}
                            <form method="POST" action="{{ route('admin.photos.reject', $photo) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="reason" required aria-label="Rejection reason" data-reject-reason
                                        class="min-w-0 flex-1 rounded-md border px-2 py-1.5 text-xs" style="border-color:var(--border-default)">
                                    <option value="">Reason…</option>
                                    @foreach ($reasons as $reason)
                                        <option value="{{ $reason }}">{{ $reason }}</option>
                                    @endforeach
                                </select>
                                <button type="submit" class="shrink-0 rounded-md border border-danger px-3 py-1.5 text-xs font-medium text-danger hover:bg-danger/10">
                                    Reject
                                </button>
                            </form>
                        @else
                            <p @class([
                                'text-xs font-medium',
                                'text-success' => $photo->approval_status === 'approved',
                                'text-danger' => $photo->approval_status === 'rejected',
                            ])>
                                {{ ucfirst($photo->approval_status) }}
                                @if ($photo->moderator) by {{ $photo->moderator->name }} @endif
                                @if ($photo->moderated_at) · {{ $photo->moderated_at->format('d M Y') }} @endif
                            </p>
                            @if ($photo->rejection_reason)
                                <p class="text-xs" style="color:var(--text-muted)">{{ $photo->rejection_reason }}</p>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $photos->links() }}</div>
    @endif
@endsection
