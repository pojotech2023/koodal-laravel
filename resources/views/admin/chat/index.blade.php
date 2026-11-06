@extends('admin.layouts.admin')

@section('title', 'Chat moderation — '.config('app.name').' Admin')

@section('admin-body')
    <h1 class="font-serif text-3xl font-bold text-ink-900">Chat moderation</h1>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">
        Private messages between members. Opening a transcript is recorded against your name, every time and on every page of a long one.
    </p>

    <form method="GET" action="{{ route('admin.chat.index') }}" class="mt-4 flex flex-wrap items-end gap-3">
        <div>
            <label for="q" class="block text-sm font-medium">Member</label>
            <input id="q" name="q" type="text" value="{{ request('q') }}" maxlength="120"
                   placeholder="MAT code or email — exact match only"
                   class="mt-1 w-72 rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                No name search and no browse: this is for investigating a specific complaint, not for looking around.
            </p>
        </div>

        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Find conversations
        </button>
    </form>

    @if (! $member)
        {{-- Not an empty table. A blank list here would read as "this member
             has no conversations" and send a moderator looking for a bug that
             is not there. --}}
        <div class="mt-6 rounded-lg border px-4 py-8 text-center text-sm" style="border-color:var(--border-default);color:var(--text-muted)">
            @if (request()->hasAny(['q', 'report_id']))
                No member matches that. Search by exact profile code or email — partial name search is deliberately not offered here.
            @else
                Name a member, or open this from a report, and their conversations will be listed.
            @endif
        </div>
    @else
        <div class="card mt-6 p-5">
            <p class="text-sm">
                Conversations involving
                <a href="{{ route('admin.users.show', $member) }}" class="font-medium text-primary-600 hover:underline">{{ $member->name }}</a>
                <span class="font-mono text-xs" style="color:var(--text-muted)">{{ $member->profile_code }}</span>
            </p>
        </div>

        <div class="mt-4">
            <x-admin.data-table :rows="$conversations" :columns="['Between', 'Messages', 'Last activity', 'Status', '']">
                @forelse ($conversations as $conversation)
                    <tr>
                        <td class="px-4 py-2.5">
                            {{ $conversation->userOne?->name }}
                            <span class="font-mono text-xs" style="color:var(--text-muted)">{{ $conversation->userOne?->profile_code }}</span>
                            <span style="color:var(--text-muted)">&harr;</span>
                            {{ $conversation->userTwo?->name }}
                            <span class="font-mono text-xs" style="color:var(--text-muted)">{{ $conversation->userTwo?->profile_code }}</span>
                        </td>
                        {{-- No preview column. `last_message_preview` is right
                             there on the row and is deliberately not shown:
                             listing is not reading, and only reading is
                             audited. --}}
                        <td class="whitespace-nowrap px-4 py-2.5">{{ number_format($conversation->messages_count ?? 0) ?: '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5">{{ $conversation->last_message_at?->format('d M Y, H:i') ?? '—' }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5">{{ ucfirst($conversation->status ?? 'active') }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-right">
                            <a href="{{ route('admin.chat.show', $conversation) }}" class="text-sm font-medium text-primary-600 hover:underline">
                                Read transcript
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-sm" style="color:var(--text-muted)">
                            This member has no conversations.
                        </td>
                    </tr>
                @endforelse
            </x-admin.data-table>
        </div>
    @endif
@endsection
