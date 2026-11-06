{{-- One profile card, shared by search, matches, interests and shortlist —
     the Blade equivalent of the frozen React site's `ProfileCard`. $profile
     is a UserProfile with `user`, `primaryPhoto` and UserProfile::WITH_LOOKUPS
     eager-loaded, and (for lists that come from ViewerState::hydrate)
     viewer_shortlisted / viewer_interest_status / viewer_is_matched already
     set — the same three dynamic attributes ProfileResource reads, so the
     heart and the interest button render in their final state without a
     query per card.

     canViewPhotos is asked per card, same as ProfileResource — an accepted,
     already-documented cost (PROGRESS.md §6, "ProfileResource still costs two
     block lookups per row"), not a new one introduced here. --}}
@props(['profile', 'viewer'])

@php
    // owner(), not ->user: canViewPhotos reaches back for `user->profile` and
    // nothing eager-loads it, so the plain relation lazy-loads once per card —
    // a query per row in production and a LazyLoadingViolationException
    // outside it. See UserProfile::owner().
    $owner = $profile->owner();
    $blurred = ! app(\App\Services\Profile\ProfileVisibility::class)->canViewPhotos($viewer, $owner);
    $photo = $profile->primaryPhoto;
    $slug = \App\Support\ProfileSlug::for($owner);
    $shortlisted = (bool) ($profile->viewer_shortlisted ?? false);
    $interestStatus = $profile->viewer_interest_status ?? null;
    $matched = (bool) ($profile->viewer_is_matched ?? false);
    $presence = app(\App\Services\UserPresenceService::class)->formatStatus($owner);
@endphp

<div class="card hover-lift group flex flex-col overflow-hidden">
    <a href="{{ route('profile.public', $slug) }}" class="relative block aspect-[4/5] overflow-hidden" style="background-color:var(--bg-subtle)">
        @if ($photo && ($photoUrl = $photo->urlFor($blurred ? 'blurred' : 'thumbnail')))
            <img
                src="{{ $photoUrl }}"
                alt=""
                class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
            >
            <div class="pointer-events-none absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-ink-900/45 to-transparent"></div>
        @else
            <div class="flex h-full w-full items-center justify-center text-sm" style="color:var(--text-muted);background:linear-gradient(135deg, var(--bg-page), var(--border-default))">No photo</div>
        @endif
    </a>

    <div class="flex flex-1 flex-col gap-1 p-4">
        <div class="flex items-center justify-between gap-1">
            <a href="{{ route('profile.public', $slug) }}" class="font-serif text-lg font-bold text-ink-900 transition-colors hover:text-primary-600">
                {{ $profile->displayNameFor($viewer) }}, {{ $owner->age() }}
                @if ($profile->is_verified)
                    <span class="ml-1 inline-grid h-5 w-5 place-items-center rounded-full bg-emerald-100 align-middle text-[11px] font-bold text-emerald-600" title="Verified">&#10003;</span>
                @endif
            </a>

            {{-- Online / Last Seen Badge --}}
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold shrink-0"
                  style="background: {{ $presence['is_online'] ? '#DCFCE7' : '#F3F4F6' }}; color: {{ $presence['is_online'] ? '#15803D' : '#6B7280' }}">
                <span class="h-2 w-2 rounded-full" style="background: {{ $presence['is_online'] ? '#22C55E' : '#9CA3AF' }}"></span>
                <span>{{ $presence['label'] }}</span>
            </span>
        </div>
        <p class="text-sm" style="color:var(--text-muted)">
            {{ collect([$profile->heightLabel(), $profile->religion?->name, $profile->city?->name])->filter()->implode(' · ') }}
        </p>
        <p class="text-sm" style="color:var(--text-muted)">
            {{ collect([$profile->education?->name, $profile->occupation?->name])->filter()->implode(' · ') }}
        </p>

        @if (isset($profile->match_reasons) && ! empty($profile->match_reasons))
            <p class="mt-1 text-xs text-primary-600">{{ implode(' · ', $profile->match_reasons) }}</p>
        @endif

        <div class="mt-3 flex flex-wrap items-center gap-2">
            @if ($owner->id !== $viewer->id)
                @if ($shortlisted)
                    <form method="POST" action="{{ route('shortlist.destroy', $owner->profile_code) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-primary-500 bg-primary-50 px-2.5 py-1.5 text-xs font-medium text-primary-700 hover:bg-primary-100 transition shadow-sm" title="Shortlisted">
                            <span>❤️</span>
                            <span class="hidden sm:inline">Shortlisted</span>
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('shortlist.store') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium hover:bg-primary-50 transition shadow-sm" style="border-color:var(--border-default)" title="Add to shortlist">
                            <span>🤍</span>
                            <span class="hidden sm:inline">Shortlist</span>
                        </button>
                    </form>
                @endif
            @endif

            @if ($matched)
                <span class="rounded-lg bg-primary-50 px-3 py-1.5 text-xs font-semibold text-primary-700">Connected</span>
                @php
                    $canChat = $viewer && ($viewer->hasPaidPlan() || app(\App\Services\KoodalGameService::class)->hasCompletedAllGames($viewer->id, $owner->id));
                @endphp
                @if ($canChat)
                    <form method="POST" action="{{ route('messages.start') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                        <button type="submit" class="rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600 transition shadow-sm">
                            Message
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('messages.start') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-pink-300 bg-pink-50 px-2.5 py-1.5 text-xs font-bold text-pink-700 hover:bg-pink-100 transition shadow-xs" title="Free users: Play all 4 games to unlock chat">
                            <span>🔒</span>
                            <span>Message</span>
                        </button>
                    </form>
                @endif
                <a href="{{ route('games.hub', $owner->id) }}"
                   class="relative inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-black text-white shadow-lg overflow-hidden transition-all duration-300 hover:scale-105 active:scale-95 group"
                   style="background: linear-gradient(135deg, #E0245E 0%, #FF4081 50%, #B81B5C 100%);">
                    <span class="absolute inset-0 w-full h-full bg-white/20 animate-pulse opacity-40"></span>
                    <span class="animate-bounce text-sm">🎮</span>
                    <span class="relative z-10">Games & Vibe</span>
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                    </span>
                </a>
            @else

                @if ($interestStatus === null)
                    <form method="POST" action="{{ route('interests.store') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                        <button type="submit" class="rounded-lg bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600">
                            Send interest
                        </button>
                    </form>
                @elseif ($interestStatus === 'pending')
                    <span class="rounded-lg px-3 py-1.5 text-xs font-medium" style="color:var(--text-muted);border:1px solid var(--border-default)">Interest sent</span>
                @elseif ($interestStatus === 'declined')
                    <span class="rounded-lg px-3 py-1.5 text-xs font-medium" style="color:var(--text-muted);border:1px solid var(--border-default)">Declined</span>
                @endif
            @endif

            {{-- Contact Request / Status button on Card --}}
            @if ($owner->id !== $viewer->id)
                @php
                    $isPaidViewer = $viewer && $viewer->hasPaidPlan();
                    $cardContactApproval = ! $isPaidViewer
                        ? \App\Models\ContactApproval::where('requester_id', $viewer->id)->where('owner_id', $owner->id)->first()
                        : null;
                @endphp
                @if ($isPaidViewer)
                    {{-- Upgraded members view contact directly --}}
                    <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($owner)) }}"
                       class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 border border-emerald-300 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition shadow-xs"
                       title="View contact details directly">
                        <span>📞</span>
                        <span>Contact</span>
                    </a>
                @elseif ($cardContactApproval && $cardContactApproval->status === 'approved')
                    <a href="{{ route('profile.public', \App\Support\ProfileSlug::for($owner)) }}"
                       class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 border border-emerald-300 px-2.5 py-1.5 text-xs font-semibold text-emerald-700 hover:bg-emerald-100 transition shadow-xs"
                       title="Contact details unlocked">
                        <span>✓</span>
                        <span>Contact Unlocked</span>
                    </a>
                @elseif ($cardContactApproval && $cardContactApproval->status === 'pending')
                    <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 border border-amber-200 px-2.5 py-1.5 text-xs font-semibold text-amber-700 shadow-xs"
                          title="Contact request sent. Waiting for member approval.">
                        <span>⏳</span>
                        <span>Contact Sent</span>
                    </span>
                @else
                    <form method="POST" action="{{ route('profile.public.contact.request', \App\Support\ProfileSlug::for($owner)) }}">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-1 rounded-lg border border-neutral-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-neutral-700 hover:bg-neutral-100 hover:border-neutral-400 transition shadow-xs"
                                title="Request permission to view phone number and email">
                            <span>📞</span>
                            <span>Contact</span>
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
</div>
