@extends('admin.layouts.admin')

@section('title', 'Transcript #'.$conversation->id.' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.chat.index', ['q' => $conversation->userOne?->profile_code]) }}" class="text-sm font-medium text-primary-600 hover:underline">
        &larr; Chat moderation
    </a>

    <h1 class="mt-3 font-serif text-3xl font-bold text-ink-900">Transcript #{{ $conversation->id }}</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        <a href="{{ route('admin.users.show', $conversation->user_one_id) }}" class="text-primary-600 hover:underline">{{ $conversation->userOne?->name }}</a>
        <span class="font-mono text-xs">{{ $conversation->userOne?->profile_code }}</span>
        &harr;
        <a href="{{ route('admin.users.show', $conversation->user_two_id) }}" class="text-primary-600 hover:underline">{{ $conversation->userTwo?->name }}</a>
        <span class="font-mono text-xs">{{ $conversation->userTwo?->profile_code }}</span>
        · {{ number_format($conversation->messages_count) }} messages · {{ ucfirst($conversation->status) }}
    </p>

    <p class="mt-3 rounded-md border border-warning bg-warning/5 px-3 py-2 text-xs text-warning">
        Oldest first, on purpose — an argument read from the end shows the retaliation and hides the provocation. This read is in the audit trail with your name on it.
    </p>

    <div class="mt-4 space-y-3">
        @forelse ($messages as $message)
            @php($isOne = $message->sender_id === $conversation->user_one_id)

            <article @class([
                'max-w-2xl rounded-lg border p-3 text-sm',
                'ml-auto' => ! $isOne,
            ]) style="border-color:var(--border-default);background-color:var(--bg-surface)">
                <header class="flex flex-wrap items-baseline justify-between gap-2 text-xs" style="color:var(--text-muted)">
                    <span class="font-medium">{{ $message->sender?->name }} <span class="font-mono">{{ $message->sender?->profile_code }}</span></span>
                    <span>
                        {{ $message->created_at->format('d M Y, H:i') }}
                        @if ($message->trashed())
                            {{-- Deleted messages are evidence: one removed
                                 seconds after it was sent is very often the
                                 complaint itself. --}}
                            · <span class="font-medium text-danger">deleted {{ $message->deleted_at?->format('d M, H:i') }}</span>
                        @endif
                    </span>
                </header>

                @if ($message->type === 'image')
                    <p class="mt-2 italic" style="color:var(--text-muted)">[image attachment]</p>
                @elseif ($message->type === 'system')
                    <p class="mt-2 italic" style="color:var(--text-muted)">{{ $message->body }}</p>
                @else
                    <p class="mt-2 whitespace-pre-line">{{ $message->body }}</p>
                @endif
            </article>
        @empty
            <p class="rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
                No messages in this conversation.
            </p>
        @endforelse
    </div>

    @if ($nextCursor)
        {{-- Keyset, not page numbers: `id` is monotonic and unique where
             created_at is neither, and it is the same cursor the API's
             transcript endpoint returns. Each click is another audited read,
             which is the intended accounting — paging is reading. --}}
        <div class="mt-6">
            <a href="{{ route('admin.chat.show', ['conversation' => $conversation, 'after' => $nextCursor]) }}"
               class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                Load the next 50 messages
            </a>
        </div>
    @endif
@endsection
