@extends('layouts.member')

@section('title', 'Contact Requests — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl font-bold text-ink-900">Contact Requests</h1>
                <p class="mt-1 text-sm text-ink-500">Manage who can view your phone number and email address.</p>
            </div>
        </div>

        {{-- Tabs --}}
        <nav class="card mt-6 inline-flex flex-wrap gap-1 p-1">
            <a href="{{ route('contact-requests.index', ['tab' => 'pending']) }}"
               class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors duration-150 {{ $tab === 'pending' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                <span>Pending Requests</span>
                @if ($pending->total() > 0)
                    <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">{{ $pending->total() }}</span>
                @endif
            </a>
            <a href="{{ route('contact-requests.index', ['tab' => 'approved']) }}"
               class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors duration-150 {{ $tab === 'approved' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                <span>Approved by You</span>
                @if ($approved->total() > 0)
                    <span class="rounded-full bg-primary-100 text-primary-700 px-2 py-0.5 text-xs font-bold">{{ $approved->total() }}</span>
                @endif
            </a>
            <a href="{{ route('contact-requests.index', ['tab' => 'sent']) }}"
               class="inline-flex items-center gap-2 rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors duration-150 {{ $tab === 'sent' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                <span>Requests Sent</span>
                @if ($sent->total() > 0)
                    <span class="rounded-full bg-primary-100 text-primary-700 px-2 py-0.5 text-xs font-bold">{{ $sent->total() }}</span>
                @endif
            </a>
        </nav>

        {{-- Tab Content --}}
        <div class="mt-6">
            @if ($tab === 'pending')
                @if ($pending->isEmpty())
                    <div class="card p-12 text-center">
                        <div class="text-4xl">📭</div>
                        <h3 class="mt-3 text-base font-semibold text-ink-900">No pending requests</h3>
                        <p class="mt-1 text-sm text-ink-500">When other members request to view your phone number or email, they will appear here for your approval.</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($pending as $item)
                            @php
                                $reqUser = $item->requester;
                                $reqProfile = $reqUser?->profile;
                                $photo = $reqProfile?->primaryPhoto;
                            @endphp
                            <div class="card overflow-hidden p-5 shadow-sm transition hover:shadow-md">
                                <div class="flex items-center gap-3">
                                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-full border bg-neutral-100" style="border-color:var(--border-default)">
                                        @if ($photo)
                                            <img src="{{ $photo->urlFor('thumbnail') }}" alt="{{ $reqUser->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full w-full place-items-center font-bold text-ink-400 text-lg">
                                                {{ strtoupper(substr($reqUser->name ?? '?', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($reqUser)) }}" class="truncate font-semibold text-ink-900 hover:text-primary-600 block">
                                            {{ $reqUser->name }}, {{ $reqUser->age() }}
                                        </a>
                                        <p class="text-xs text-ink-500">{{ $reqUser->profile_code }}</p>
                                        <p class="text-xs text-ink-500 truncate mt-0.5">
                                            {{ $reqProfile?->occupation?->name ?? 'Member' }} · {{ $reqProfile?->city?->name ?? 'Tamil Nadu' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 border-t pt-3" style="border-color:var(--border-default)">
                                    <p class="text-xs text-ink-400 mb-3">Requested {{ $item->created_at->diffForHumans() }}</p>
                                    <div class="flex items-center gap-2">
                                        <form method="POST" action="{{ route('contact-requests.approve', $item) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition">
                                                ✓ Approve
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('contact-requests.reject', $item) }}" class="flex-1">
                                            @csrf
                                            <button type="submit" class="w-full rounded-lg border border-neutral-300 px-3 py-2 text-xs font-semibold text-ink-700 hover:bg-neutral-100 transition">
                                                ✕ Decline
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $pending->links() }}</div>
                @endif

            @elseif ($tab === 'approved')
                @if ($approved->isEmpty())
                    <div class="card p-12 text-center">
                        <div class="text-4xl">🛡️</div>
                        <h3 class="mt-3 text-base font-semibold text-ink-900">No approved members</h3>
                        <p class="mt-1 text-sm text-ink-500">Members you've approved contact visibility for will be listed here.</p>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($approved as $item)
                            @php
                                $reqUser = $item->requester;
                                $reqProfile = $reqUser?->profile;
                                $photo = $reqProfile?->primaryPhoto;
                            @endphp
                            <div class="card overflow-hidden p-5 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full border bg-neutral-100" style="border-color:var(--border-default)">
                                        @if ($photo)
                                            <img src="{{ $photo->urlFor('thumbnail') }}" alt="{{ $reqUser->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full w-full place-items-center font-bold text-ink-400">
                                                {{ strtoupper(substr($reqUser->name ?? '?', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($reqUser)) }}" class="truncate font-semibold text-ink-900 hover:text-primary-600 block">
                                            {{ $reqUser->name }}
                                        </a>
                                        <p class="text-xs text-ink-500">{{ $reqUser->profile_code }}</p>
                                        <span class="inline-flex items-center gap-1 mt-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                            ✓ Access Granted
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $approved->links() }}</div>
                @endif

            @elseif ($tab === 'sent')
                @if ($sent->isEmpty())
                    <div class="card p-12 text-center">
                        <div class="text-4xl">📋</div>
                        <h3 class="mt-3 text-base font-semibold text-ink-900">No requests sent yet</h3>
                        <p class="mt-1 text-sm text-ink-500 max-w-md mx-auto">
                            To view another member's phone number and email, visit their profile and click <strong>"Request Contact Details"</strong>, or click <strong>"📞 Contact"</strong> directly on any match card.
                        </p>
                        <div class="mt-5">
                            <a href="{{ route('matches.recommended') }}" class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md transition hover:scale-105 active:scale-95" style="background: linear-gradient(135deg, #E0245E, #B81B5C);">
                                <span>🔍</span>
                                <span>Browse Matches to Request Contacts</span>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($sent as $item)
                            @php
                                $targetUser = $item->owner;
                                $targetProfile = $targetUser?->profile;
                                $photo = $targetProfile?->primaryPhoto;
                            @endphp
                            <div class="card overflow-hidden p-5 shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-full border bg-neutral-100" style="border-color:var(--border-default)">
                                        @if ($photo)
                                            <img src="{{ $photo->urlFor('thumbnail') }}" alt="{{ $targetUser->name }}" class="h-full w-full object-cover">
                                        @else
                                            <div class="grid h-full w-full place-items-center font-bold text-ink-400">
                                                {{ strtoupper(substr($targetUser->name ?? '?', 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($targetUser)) }}" class="truncate font-semibold text-ink-900 hover:text-primary-600 block">
                                            {{ $targetUser->name }}
                                        </a>
                                        <p class="text-xs text-ink-500">{{ $targetUser->profile_code }}</p>
                                        <div class="mt-1">
                                            @if ($item->status === 'approved')
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                                    ✓ Approved - Ready to View
                                                </span>
                                            @elseif ($item->status === 'pending')
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded">
                                                    ⏳ Pending Approval
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-red-700 bg-red-50 px-2 py-0.5 rounded">
                                                    ✕ Declined
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3 border-t pt-2 text-right">
                                    <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($targetUser)) }}" class="text-xs font-semibold text-primary-600 hover:underline">
                                        View Profile &rarr;
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $sent->links() }}</div>
                @endif
            @endif
        </div>
    </div>
@endsection
