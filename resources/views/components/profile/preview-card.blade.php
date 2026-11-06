{{-- The signed-out sibling of <x-profile.card>, for /browse.

     A separate component rather than a `$viewer === null` branch inside that
     one, because almost nothing survives the transition: every button on the
     member card (shortlist, send interest, message) posts to a route behind
     `auth:web`, the heart and interest states come from ViewerState::hydrate
     which needs a viewer to hydrate against, and the name/photo links go to
     `profile.public`, which is also member-only. What is left is a photo, a
     masked name and three lines of attributes — this file.

     Every profile reaching here has `profile_visibility = 'all'` (enforced in
     SQL by SearchService::previewQuery, not here), so the owner has
     deliberately published to the open web. Photos are still asked for
     separately: `photo_visibility` is its own setting, and the common case —
     published profile, members-only photos — must get the blurred derivative,
     not the original. canViewPhotos(null, $owner) is the same call the member
     card makes, with a null viewer; `user.privacySettings` is eager-loaded and
     `owner()` wires the inverse `user->profile` the visibility check also
     reaches for, so it costs no query. --}}
@props(['profile'])

@php
    $owner = $profile->owner();
    $photo = $profile->primaryPhoto;
    $blurred = ! app(\App\Services\Profile\ProfileVisibility::class)->canViewPhotos(null, $owner);
@endphp

{{-- Links to register, not to the profile: `profile.public` is behind
     `auth:web`, so linking there would send both visitors and crawlers into a
     login redirect. Registering is the honest next step and the one the page
     exists to produce. --}}
<a
    href="{{ route('register') }}"
    class="flex flex-col overflow-hidden rounded-lg border transition hover:shadow-md"
    style="border-color:var(--border-default);background-color:var(--bg-surface)"
>
    <div class="aspect-[4/5] overflow-hidden bg-gray-100">
        @if ($photo && ($photoUrl = $photo->urlFor($blurred ? 'blurred' : 'thumbnail')))
            <img src="{{ $photoUrl }}" alt="" class="h-full w-full object-cover" loading="lazy">
        @else
            <div class="flex h-full w-full items-center justify-center text-sm" style="color:var(--text-muted)">No photo</div>
        @endif
    </div>

    <div class="flex flex-1 flex-col gap-1 p-4">
        <p class="font-medium">
            {{ $profile->displayNameFor(null) }}, {{ $owner->age() }}
            @if ($profile->is_verified || ($owner->is_aadhaar_verified ?? false))
                <span class="ml-1 inline-grid h-5 w-5 place-items-center rounded-full bg-emerald-100 align-middle text-[11px] font-bold text-emerald-600 shadow-sm" title="Verified">&#10003;</span>
            @endif
        </p>
        <p class="text-sm" style="color:var(--text-muted)">
            {{ collect([$profile->heightLabel(), $profile->religion?->name, $profile->city?->name])->filter()->implode(' · ') }}
        </p>
        <p class="text-sm" style="color:var(--text-muted)">
            {{ collect([$profile->education?->name, $profile->occupation?->name])->filter()->implode(' · ') }}
        </p>
        <p class="mt-2 text-xs font-medium text-primary-600">Join free to view full profile</p>
    </div>
</a>
