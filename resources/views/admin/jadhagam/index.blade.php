@extends('admin.layouts.admin')

@section('title', 'Jadhagam Moderation — '.config('app.name').' Admin')

@section('admin-body')
    <div class="mb-4 flex gap-2 border-b pb-3" style="border-color:var(--border-default)">
        <a href="{{ route('admin.photos.index') }}" class="rounded-md px-3 py-1.5 text-xs font-medium hover:bg-primary-50" style="color:var(--text-muted)">
            Profile Photos
        </a>
        <a href="{{ route('admin.jadhagam.index') }}" class="rounded-md bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700">
            Jadhagam (Horoscope) Photos
        </a>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">Jadhagam moderation</h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Review member horoscope charts. Approved charts are visible on prospective matching profiles; rejected ones remain private.
            </p>
        </div>

        <nav class="flex rounded-md border" style="border-color:var(--border-default)" aria-label="Queue status">
            @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                <a href="{{ route('admin.jadhagam.index', ['status' => $value]) }}"
                   @class([
                       'px-4 py-2 text-sm font-medium first:rounded-l-md last:rounded-r-md',
                       'bg-primary-50 text-primary-700 font-semibold' => $status === $value,
                   ])
                   @if ($status === $value) aria-current="page" @endif>
                    {{ $label }}
                    @if ($value === 'pending' && $pendingCount > 0)
                        <span class="ml-1 rounded-full bg-warning/15 px-1.5 py-0.5 text-xs text-warning">{{ $pendingCount }}</span>
                    @elseif ($value === 'approved' && $approvedCount > 0)
                        <span class="ml-1 rounded-full bg-success/15 px-1.5 py-0.5 text-xs text-success">{{ $approvedCount }}</span>
                    @elseif ($value === 'rejected' && $rejectedCount > 0)
                        <span class="ml-1 rounded-full bg-danger/15 px-1.5 py-0.5 text-xs text-danger">{{ $rejectedCount }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-success bg-success/10 p-3 text-sm font-medium text-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($jadhagams->isEmpty())
        <p class="mt-6 rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
            @if ($status === 'pending')
                Nothing waiting. The queue is clear.
            @else
                No {{ $status }} Jadhagam records.
            @endif
        </p>
    @else
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
            @foreach ($jadhagams as $jadhagam)
                <article class="card overflow-hidden">
                    <a href="{{ $jadhagam->url() }}" target="_blank" rel="noopener" title="Click to view full image">
                        <img src="{{ $jadhagam->url() }}" alt="Jadhagam chart" loading="lazy" class="aspect-square w-full object-cover">
                    </a>

                    <div class="space-y-2 p-3 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <a href="{{ route('admin.users.show', $jadhagam->user_id) }}" class="block truncate font-medium text-primary-600 hover:underline">
                                    {{ $jadhagam->user?->name }}
                                </a>
                                <p class="truncate font-mono text-xs" style="color:var(--text-muted)">{{ $jadhagam->user?->profile_code }}</p>
                            </div>
                        </div>

                        <p class="text-xs" style="color:var(--text-muted)">
                            Uploaded {{ $jadhagam->created_at?->diffForHumans(short: true) }}
                        </p>

                        @if ($status === 'pending')
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.jadhagam.approve', $jadhagam) }}">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-success px-3 py-1.5 text-xs font-medium text-white hover:opacity-90">
                                        Approve
                                    </button>
                                </form>
                            </div>

                            <form method="POST" action="{{ route('admin.jadhagam.reject', $jadhagam) }}" class="flex items-center gap-2">
                                @csrf
                                <select name="reason" required aria-label="Rejection reason"
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
                                'text-success' => $jadhagam->approval_status === 'approved',
                                'text-danger' => $jadhagam->approval_status === 'rejected',
                            ])>
                                {{ ucfirst($jadhagam->approval_status) }}
                                @if ($jadhagam->moderator) by {{ $jadhagam->moderator->name }} @endif
                                @if ($jadhagam->moderated_at) · {{ $jadhagam->moderated_at->format('d M Y') }} @endif
                            </p>
                            @if ($jadhagam->rejection_reason)
                                <p class="text-xs" style="color:var(--text-muted)">{{ $jadhagam->rejection_reason }}</p>
                            @endif
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $jadhagams->links() }}
        </div>
    @endif
@endsection
