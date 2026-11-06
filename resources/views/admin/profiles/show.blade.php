@extends('admin.layouts.admin')

@section('title', 'Review '.$profile->user?->name.' — '.config('app.name').' Admin')

@section('admin-body')
    @php($user = $profile->user)

    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.profiles.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Profile approvals</a>

        @if ($profile->isPendingApproval() && $remaining > 1)
            <p class="text-sm" style="color:var(--text-muted)">{{ $remaining - 1 }} more waiting after this one</p>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">
                {{ $user?->name }}
                <a href="{{ route('admin.users.show', $user) }}" class="font-mono text-base text-primary-600 hover:underline">{{ $user?->profile_code }}</a>
            </h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                {{ ucfirst($user?->gender ?? '') }} · {{ $user?->age() }} yrs ·
                Created by {{ str_replace('_', ' ', $profile->profile_created_by) }} ·
                Registered {{ $user?->created_at?->format('d M Y') }} ·
                {{ $profile->completion_percentage }}% complete
            </p>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-sm font-medium',
            'bg-warning/10 text-warning' => $profile->approval_status === 'pending',
            'bg-success/10 text-success' => $profile->approval_status === 'approved',
            'bg-danger/10 text-danger' => $profile->approval_status === 'rejected',
        ])>{{ ucfirst($profile->approval_status) }}</span>
    </div>

    {{-- The decision bar sits above the fields, not below them: a moderator
         who has already read the profile should not scroll back past it to
         act. Approve is a plain POST; reject carries the picker from
         config('matrimony.reports.rejection_reasons.profile') — the same
         array `RejectProfileRequest` validates against, so the picker and the
         validation cannot drift (docs/06-admin-panel-guide.md §6: free text
         would put four moderators' wording in front of members). --}}
    @if ($profile->isPendingApproval())
        <div class="card mt-4 flex flex-wrap items-end gap-3 p-5">
            <form method="POST" action="{{ route('admin.profiles.approve', $profile) }}">
                @csrf
                <button type="submit" class="rounded-md bg-success px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                    Approve profile
                </button>
            </form>

            <form method="POST" action="{{ route('admin.profiles.reject', $profile) }}" class="flex flex-wrap items-end gap-2">
                @csrf
                <x-form.select name="reason" label="Rejection reason" placeholder="Choose a reason" required :options="collect($reasons)->map(fn ($r) => ['id' => $r, 'name' => $r])->all()" />
                <button type="submit" class="rounded-md border border-danger px-4 py-2 text-sm font-medium text-danger hover:bg-danger/10">
                    Reject
                </button>
            </form>

            <p class="basis-full text-xs" style="color:var(--text-muted)">
                The member is notified either way — a rejection sends the reason above verbatim, which is what tells them what to change.
            </p>
        </div>
    @elseif ($profile->rejection_reason)
        <div class="mt-4 rounded-lg border border-danger bg-danger/5 p-4 text-sm text-danger">
            Rejected: {{ $profile->rejection_reason }}
        </div>
    @endif

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Left column: everything the member wrote or uploaded themselves.
             This is the half a moderation decision actually turns on — two of
             the four configured rejection reasons ("Contact details in free
             text", "Offensive content") can only be seen here — so it leads,
             and the structured lookups sit opposite it rather than above. --}}
        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="card-title">Photos ({{ $user?->photos->count() }})</h2>

                @if ($user?->photos->isNotEmpty())
                    <div class="mt-3 grid grid-cols-3 gap-2">
                        @foreach ($user->photos as $photo)
                            <figure>
                                <img src="{{ $photo->urlFor('thumbnail') }}" alt="" class="aspect-square w-full rounded-md object-cover">
                                <figcaption @class([
                                    'mt-1 text-center text-xs',
                                    'text-success' => $photo->approval_status === 'approved',
                                    'text-danger' => $photo->approval_status === 'rejected',
                                    'text-warning' => $photo->approval_status === 'pending',
                                ])>{{ ucfirst($photo->approval_status) }}</figcaption>
                            </figure>
                        @endforeach
                    </div>
                    <p class="mt-3 text-xs" style="color:var(--text-muted)">
                        Photos are moderated separately — approving this profile does not approve them.
                    </p>
                @else
                    <p class="mt-3 text-sm" style="color:var(--text-muted)">No photos uploaded.</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="card-title">Jadhagam (Horoscope) ({{ $user?->jadhagams->count() }})</h2>

                @if ($user?->jadhagams->isNotEmpty())
                    <div class="mt-3 grid grid-cols-2 gap-3">
                        @foreach ($user->jadhagams as $jadhagam)
                            <div class="rounded-md border p-2 text-center" style="border-color:var(--border-default)">
                                <a href="{{ $jadhagam->url() }}" target="_blank" class="block">
                                    <img src="{{ $jadhagam->url() }}" alt="Jadhagam" class="aspect-[3/4] w-full rounded object-contain bg-neutral-100">
                                </a>
                                <span @class([
                                    'mt-1 inline-block text-xs font-semibold',
                                    'text-success' => $jadhagam->approval_status === 'approved',
                                    'text-danger' => $jadhagam->approval_status === 'rejected',
                                    'text-warning' => $jadhagam->approval_status === 'pending',
                                ])>{{ ucfirst($jadhagam->approval_status) }}</span>

                                <div class="mt-2 flex gap-1 justify-center">
                                    @if ($jadhagam->approval_status !== 'approved')
                                        <form method="POST" action="{{ route('admin.jadhagam.approve', $jadhagam) }}">
                                            @csrf
                                            <button type="submit" class="rounded bg-success px-2 py-0.5 text-xs text-white">Approve</button>
                                        </form>
                                    @endif
                                    @if ($jadhagam->approval_status !== 'rejected')
                                        <form method="POST" action="{{ route('admin.jadhagam.reject', $jadhagam) }}">
                                            @csrf
                                            <button type="submit" class="rounded border border-danger px-2 py-0.5 text-xs text-danger">Reject</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="mt-3 text-sm" style="color:var(--text-muted)">No Jadhagam uploaded.</p>
                @endif
            </section>

            <section class="card p-5">
                <h2 class="card-title">In their own words</h2>
                <dl class="mt-3 space-y-3 text-sm">
                    <div>
                        <dt class="text-xs" style="color:var(--text-muted)">About me</dt>
                        <dd class="whitespace-pre-line">{{ $profile->about_me ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs" style="color:var(--text-muted)">Expectations</dt>
                        <dd class="whitespace-pre-line">{{ $profile->expectations ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs" style="color:var(--text-muted)">Family description</dt>
                        <dd class="whitespace-pre-line">{{ $profile->family_description ?: '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="card-title">Contact on file</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Email" :value="$user?->email" />
                    <x-profile.detail-row label="Mobile" :value="$user?->mobile" />
                    <x-profile.detail-row label="Email verified" :value="$user?->email_verified_at ? 'Yes' : 'No'" />
                    <x-profile.detail-row label="Mobile verified" :value="$user?->mobile_verified_at ? 'Yes' : 'No'" />
                    <x-profile.detail-row label="Registration source" :value="ucfirst($user?->registration_source ?? '—')" />
                    <x-profile.detail-row label="Account status" :value="ucfirst($user?->status ?? '—')" />
                </dl>
            </section>
        </div>

        {{-- Right column: the structured fields, in the same order the
             completion wizard collects them, so a moderator comparing a
             profile against what the member was asked reads them in one
             direction. --}}
        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="card-title">Basic</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Marital status" :value="$profile->maritalStatus?->name" />
                    <x-profile.detail-row label="Height" :value="$profile->height_cm ? $profile->height_cm.' cm' : null" />
                    <x-profile.detail-row label="Weight" :value="$profile->weight_kg ? $profile->weight_kg.' kg' : null" />
                    <x-profile.detail-row label="Body type" :value="$profile->body_type" />
                    <x-profile.detail-row label="Complexion" :value="$profile->complexion" />
                    <x-profile.detail-row label="Physical status" :value="$profile->physical_status" />
                    <x-profile.detail-row label="Children" :value="$profile->children_count" />
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="card-title">Religion &amp; location</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Religion" :value="$profile->religion?->name" />
                    <x-profile.detail-row label="Community" :value="$profile->community?->name" />
                    <x-profile.detail-row label="Sub-community" :value="$profile->subCommunity?->name" />
                    <x-profile.detail-row label="Mother tongue" :value="$profile->motherTongue?->name" />
                    <x-profile.detail-row label="Caste" :value="$profile->caste" />
                    <x-profile.detail-row label="Gothra" :value="$profile->gothra" />
                    <x-profile.detail-row label="Star" :value="$profile->star" />
                    <x-profile.detail-row label="Raasi" :value="$profile->raasi" />
                    <x-profile.detail-row label="Manglik" :value="$profile->manglik" />
                    <x-profile.detail-row label="Birth place" :value="$profile->birth_place" />
                    <x-profile.detail-row label="Country" :value="$profile->country?->name" />
                    <x-profile.detail-row label="State" :value="$profile->state?->name" />
                    <x-profile.detail-row label="City" :value="$profile->city?->name" />
                    <x-profile.detail-row label="Residency" :value="$profile->residency_status" />
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="card-title">Education &amp; career</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Education" :value="$profile->education?->name" />
                    <x-profile.detail-row label="Detail" :value="$profile->education_detail" />
                    <x-profile.detail-row label="College" :value="$profile->college" />
                    <x-profile.detail-row label="Specialization" :value="$profile->specialization" />
                    <x-profile.detail-row label="Occupation" :value="$profile->occupation?->name" />
                    <x-profile.detail-row label="Company" :value="$profile->company" />
                    <x-profile.detail-row label="Job type" :value="$profile->job_type" />
                    <x-profile.detail-row label="Income" :value="$profile->incomeRange?->name" />
                </dl>
            </section>

            <section class="card p-5">
                <h2 class="card-title">Family &amp; lifestyle</h2>
                <dl class="mt-3 grid grid-cols-1 gap-x-4 gap-y-2 text-sm sm:grid-cols-2">
                    <x-profile.detail-row label="Father's occupation" :value="$profile->father_occupation" />
                    <x-profile.detail-row label="Mother's occupation" :value="$profile->mother_occupation" />
                    <x-profile.detail-row label="Brothers" :value="$profile->brothers_count" />
                    <x-profile.detail-row label="Sisters" :value="$profile->sisters_count" />
                    <x-profile.detail-row label="Family type" :value="$profile->family_type" />
                    <x-profile.detail-row label="Family status" :value="$profile->family_status" />
                    <x-profile.detail-row label="Family values" :value="$profile->family_values" />
                    <x-profile.detail-row label="Family location" :value="$profile->family_location" />
                    <x-profile.detail-row label="Diet" :value="$profile->diet" />
                    <x-profile.detail-row label="Smoking" :value="$profile->smoking" />
                    <x-profile.detail-row label="Drinking" :value="$profile->drinking" />
                    <x-profile.detail-row label="Hobbies" :value="collect($profile->hobbies)->join(', ')" />
                    <x-profile.detail-row label="Interests" :value="collect($profile->interests)->join(', ')" />
                </dl>
            </section>
        </div>
    </div>
@endsection
