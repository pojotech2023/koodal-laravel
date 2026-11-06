@extends('layouts.member')

@section('title', $profile->displayNameFor($viewer).' — '.config('app.name'))

@section('body')
    @php
        $shortlisted = (bool) ($profile->viewer_shortlisted ?? false);
        $interestStatus = $profile->viewer_interest_status ?? null;
        $matched = (bool) ($profile->viewer_is_matched ?? false);
    @endphp

    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="card flex flex-wrap items-start justify-between gap-4 p-6">
            <div>
                <h1 class="font-serif text-3xl font-bold text-ink-900">
                    {{ $profile->displayNameFor($viewer) }}, {{ $owner->age() }}
                    @if ($profile->is_verified || $owner->is_aadhaar_verified)
                        <span class="ml-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 align-middle text-xs font-semibold text-emerald-700 shadow-sm" title="Aadhaar Verified">
                            <svg class="h-3 w-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            Verified
                        </span>
                    @endif
                </h1>
                <p class="mt-1 text-sm" style="color:var(--text-muted)">{{ $owner->profile_code }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if ($owner->id !== $viewer->id)
                    @if ($shortlisted)
                        <form method="POST" action="{{ route('shortlist.destroy', $owner->profile_code) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border border-primary-500 bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700 hover:bg-primary-100 transition shadow-sm">
                                <span class="text-base text-primary-600">❤️</span>
                                <span>Shortlisted</span>
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('shortlist.store') }}">
                            @csrf
                            <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-md border px-4 py-2 text-sm font-medium hover:bg-primary-50 transition shadow-sm" style="border-color:var(--border-default)">
                                <span class="text-base">🤍</span>
                                <span>Shortlist</span>
                            </button>
                        </form>
                    @endif
                @endif

                @if ($matched)
                    <span class="rounded-md bg-primary-50 px-4 py-2 text-sm font-medium text-primary-700">Connected</span>
                    <form method="POST" action="{{ route('messages.start') }}">
                        @csrf
                        <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Message</button>
                    </form>
                    <a href="{{ route('games.hub', $owner->id) }}"
                       class="relative inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-black text-white shadow-lg overflow-hidden transition-all duration-300 hover:scale-105 active:scale-95 group"
                       style="background: linear-gradient(135deg, #E0245E 0%, #FF4081 50%, #B81B5C 100%);">
                        <span class="animate-bounce text-sm">🎮</span>
                        <span>Games & Vibe</span>
                    </a>
                @elseif ($owner->id !== $viewer->id)
                    @if ($interestStatus === null)
                        <form method="POST" action="{{ route('interests.store') }}">
                            @csrf
                            <input type="hidden" name="profile_code" value="{{ $owner->profile_code }}">
                            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">Send interest</button>
                        </form>
                    @elseif ($interestStatus === 'pending')
                        <span class="rounded-md px-4 py-2 text-sm font-medium" style="color:var(--text-muted);border:1px solid var(--border-default)">Interest sent</span>
                    @elseif ($interestStatus === 'declined')
                        <span class="rounded-md px-4 py-2 text-sm font-medium" style="color:var(--text-muted);border:1px solid var(--border-default)">Declined</span>
                    @endif
                @endif

                @if ($owner->id !== $viewer->id)
                    {{-- 🔮 Rasi Porutham Button (Link to dedicated Rasi Porutham details page) --}}
                    <a href="{{ route('profile.rasi', $owner->profile_code) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-black text-white shadow-md transition hover:scale-105 active:scale-95"
                       style="background: linear-gradient(135deg, #9333ea, #6b21a8);">
                        <span>🔮</span>
                        <span>Rasi Porutham</span>
                        @if(!empty($rasiPorutham['is_ready']))
                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">{{ $rasiPorutham['score'] }}%</span>
                        @endif
                    </a>

                    {{-- 💝 Partner Preference Match Button (Link to dedicated Preference details page) --}}
                    <a href="{{ route('profile.preference', $owner->profile_code) }}"
                       class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-sm font-black text-white shadow-md transition hover:scale-105 active:scale-95"
                       style="background: linear-gradient(135deg, #ec4899, #be185d);">
                        <span>💝</span>
                        <span>Preference Match</span>
                        @if(!empty($preferenceMatch['score']))
                            <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">{{ $preferenceMatch['score'] }}%</span>
                        @endif
                    </a>
                @endif
            </div>
        </div>

        {{-- Photos --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Photos</h2>
            @if ($profile->photos->isEmpty())
                <p class="mt-2 text-sm" style="color:var(--text-muted)">No photos yet.</p>
            @else
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($profile->photos as $photo)
                        <div class="aspect-[4/5] overflow-hidden rounded-lg border shadow-sm" style="border-color:var(--border-default)">
                            <img src="{{ $photo->urlFor($canSeePhotos ? 'full' : 'blurred') }}" alt="" class="h-full w-full object-cover">
                        </div>
                    @endforeach
                </div>
                @if (! $canSeePhotos)
                    <p class="mt-2 text-sm" style="color:var(--text-muted)">Photos are blurred until you connect with {{ $profile->displayNameFor($viewer) }}.</p>
                @endif
            @endif
        </section>

        {{-- Jadhagam (Horoscope) - Displayed if approved by admin (or if viewing own profile) --}}
        @php
            $visibleJadhagams = ($viewer && $viewer->id === $owner->id)
                ? ($owner->approvedJadhagams->isNotEmpty() ? $owner->approvedJadhagams : $owner->jadhagams)
                : $owner->approvedJadhagams;
        @endphp
        @if ($visibleJadhagams && $visibleJadhagams->isNotEmpty())
            <section class="card mt-6 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Jadhagam (Horoscope)</h2>
                    @if ($viewer && $viewer->id === $owner->id)
                        <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="text-xs font-medium text-primary-600 hover:underline">Manage Jadhagam &rarr;</a>
                    @endif
                </div>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    @foreach ($visibleJadhagams as $jadhagam)
                        <div class="overflow-hidden rounded-lg border bg-neutral-50 p-2 shadow-sm" style="border-color:var(--border-default)">
                            <a href="{{ $jadhagam->url() }}" target="_blank" title="Click to open full Jadhagam" class="block aspect-[3/4] overflow-hidden">
                                <img src="{{ $jadhagam->url() }}" alt="Horoscope Chart" class="h-full w-full object-contain hover:scale-105 transition duration-200">
                            </a>
                            <div class="mt-2 flex items-center justify-between">
                                <a href="{{ $jadhagam->url() }}" target="_blank" class="text-xs font-medium text-primary-600 hover:underline">
                                    View full Jadhagam &rarr;
                                </a>
                                @if ($jadhagam->approval_status !== 'approved')
                                    <span class="rounded bg-warning/20 px-1.5 py-0.5 text-[10px] font-semibold text-warning">
                                        {{ ucfirst($jadhagam->approval_status) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Contact --}}
        <section class="card mt-6 p-6">
            <div class="flex items-center justify-between">
                <h2 class="card-title">Contact details</h2>
                <span class="text-xs text-ink-400">🔒 Privacy Protected</span>
            </div>

            @php
                $isPaidViewer = $viewer && $viewer->hasPaidPlan();
            @endphp

            @if ($viewer && $viewer->id === $owner->id)
                {{-- Own Profile --}}
                <div class="mt-3 space-y-1">
                    <p class="text-sm"><strong>Mobile:</strong> {{ $owner->mobile }}</p>
                    <p class="text-sm"><strong>Email:</strong> {{ $owner->email }}</p>
                    <span class="inline-block mt-1 text-xs text-emerald-600 font-semibold bg-emerald-50 px-2 py-0.5 rounded">Your own profile</span>
                </div>

            @elseif ($isPaidViewer)
                {{-- Paid / Upgraded Member: View contact directly without approval --}}
                @if ($contact)
                    <div class="mt-3 space-y-2 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4">
                        <p class="text-sm text-ink-900"><strong>Mobile:</strong> <a href="tel:{{ $contact['mobile'] }}" class="font-semibold text-primary-600 hover:underline">{{ $contact['mobile'] }}</a></p>
                        @if (!empty($contact['email']))
                            <p class="text-sm text-ink-900"><strong>Email:</strong> <a href="mailto:{{ $contact['email'] }}" class="font-semibold text-primary-600 hover:underline">{{ $contact['email'] }}</a></p>
                        @endif
                        @if (!empty($contact['mobile']))
                            <p class="text-sm text-ink-900">
                                <strong>WhatsApp:</strong> <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $contact['mobile']) }}" target="_blank" class="font-semibold text-emerald-600 hover:underline">Chat on WhatsApp &rarr;</a>
                            </p>
                        @endif
                        @if ($contact['views_remaining'] !== null)
                            <p class="mt-2 text-xs" style="color:var(--text-muted)">{{ $contact['views_remaining'] }} contact views remaining this month.</p>
                        @endif
                    </div>
                @else
                    <div class="mt-3">
                        <div class="mb-3 inline-flex items-center gap-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 px-3 py-1 rounded-md">
                            <span>⭐</span> Upgraded Member Benefit: Access contact details directly without waiting for approval.
                        </div>
                        <p class="mb-3 text-sm text-ink-600">
                            Masked Mobile: <span class="font-mono font-semibold">{{ $owner->mobile ? substr($owner->mobile, 0, 3).'*****'.substr($owner->mobile, -2) : '***' }}</span>
                        </p>
                        <form method="POST" action="{{ route('profile.public.contact', \App\Support\ProfileSlug::for($owner)) }}">
                            @csrf
                            <button type="submit" class="rounded-lg bg-primary-500 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-600 shadow-sm transition">
                                Reveal Contact Details
                            </button>
                        </form>
                    </div>
                @endif

            @else
                {{-- Free Member: Requires Owner Approval --}}
                @if ($contactApproval && $contactApproval->status === 'approved')
                    {{-- Approved by Owner -> Directly Show Details! --}}
                    <div class="mt-3">
                        <div class="mb-3 inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-300 px-3 py-1.5 rounded-lg shadow-xs">
                            <span class="text-sm">✓</span>
                            <span>Contact Access Approved by {{ $profile->displayNameFor($viewer) }}</span>
                        </div>
                        <div class="space-y-2 rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-xs">
                            <p class="text-sm text-ink-900">
                                <strong>Mobile:</strong> <a href="tel:{{ $owner->mobile }}" class="font-bold text-primary-600 hover:underline ml-1">{{ $owner->mobile }}</a>
                            </p>
                            @if ($owner->email)
                                <p class="text-sm text-ink-900">
                                    <strong>Email:</strong> <a href="mailto:{{ $owner->email }}" class="font-bold text-primary-600 hover:underline ml-1">{{ $owner->email }}</a>
                                </p>
                            @endif
                            @if ($owner->mobile)
                                <p class="text-sm text-ink-900">
                                    <strong>WhatsApp:</strong> <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $owner->mobile) }}" target="_blank" class="font-bold text-emerald-600 hover:underline ml-1">Chat on WhatsApp &rarr;</a>
                                </p>
                            @endif
                        </div>
                    </div>
                @elseif ($contactApproval && $contactApproval->status === 'pending')
                    <div class="mt-3">
                        <div class="inline-flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3.5 py-2 text-sm font-medium text-amber-800">
                            <span class="text-base">⏳</span>
                            <span>Contact request sent. Waiting for {{ $profile->displayNameFor($viewer) }}'s approval.</span>
                        </div>
                        <p class="mt-2 text-xs" style="color:var(--text-muted)">
                            Contact details remain masked until the member approves your request:
                            <span class="font-mono text-ink-700 ml-1 font-semibold">{{ $owner->mobile ? substr($owner->mobile, 0, 3).'*****'.substr($owner->mobile, -2) : '***' }}</span>
                        </p>
                    </div>
                @elseif ($contactApproval && $contactApproval->status === 'rejected')
                    <div class="mt-3">
                        <div class="inline-flex items-center gap-2 rounded-lg bg-red-50 border border-red-200 px-3.5 py-2 text-sm font-medium text-red-700">
                            <span>❌</span>
                            <span>Contact request was declined by {{ $profile->displayNameFor($viewer) }}.</span>
                        </div>
                        <form method="POST" action="{{ route('profile.public.contact.request', \App\Support\ProfileSlug::for($owner)) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="rounded-md border border-primary-500 px-3.5 py-1.5 text-xs font-medium text-primary-600 hover:bg-primary-50 transition">
                                Request Again
                            </button>
                        </form>
                    </div>
                @else
                    <div class="mt-3">
                        <p class="text-sm" style="color:var(--text-muted)">
                            Phone & Email are protected. Send a request to {{ $profile->displayNameFor($viewer) }} to get approval to view contact details.
                        </p>
                        <p class="mt-1 font-mono text-xs text-ink-500">
                            Mobile: {{ $owner->mobile ? substr($owner->mobile, 0, 3).'*****'.substr($owner->mobile, -2) : '***' }}
                        </p>
                        <form method="POST" action="{{ route('profile.public.contact.request', \App\Support\ProfileSlug::for($owner)) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 shadow-sm transition">
                                <span>📞</span>
                                <span>Request Contact Details</span>
                            </button>
                        </form>
                    </div>
                @endif
            @endif
        </section>

        {{-- Basic --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Basic details</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Height" :value="$profile->heightLabel()" />
                <x-profile.detail-row label="Marital status" :value="$profile->maritalStatus?->name" />
                <x-profile.detail-row label="Physical status" :value="$profile->physical_status" />
            </dl>
        </section>

        {{-- Religious --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Religion & background</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Religion" :value="$profile->religion?->name" />
                <x-profile.detail-row label="Community" :value="$profile->community?->name" />
                <x-profile.detail-row label="Mother tongue" :value="$profile->motherTongue?->name" />
            </dl>
        </section>

        {{-- Education & career --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Education & career</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Education" :value="$profile->education?->name" />
                <x-profile.detail-row label="Occupation" :value="$profile->occupation?->name" />
                <x-profile.detail-row label="Annual income" :value="$profile->incomeRange?->name" />
            </dl>
        </section>

        {{-- Location --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Location</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="State" :value="$profile->state?->name" />
                <x-profile.detail-row label="City" :value="$profile->city?->name" />
            </dl>
        </section>

        {{-- Family --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Family</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Family type" :value="$profile->family_type ? ucfirst($profile->family_type) : null" />
                <x-profile.detail-row label="Family status" :value="$profile->family_status ? str($profile->family_status)->replace('_', ' ')->ucfirst() : null" />
                <x-profile.detail-row label="Father's occupation" :value="$profile->father_occupation" />
                <x-profile.detail-row label="Mother's occupation" :value="$profile->mother_occupation" />
            </dl>
        </section>

        {{-- Lifestyle --}}
        <section class="card mt-6 p-6">
            <h2 class="card-title">Lifestyle</h2>
            <dl class="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <x-profile.detail-row label="Diet" :value="$profile->diet" />
                <x-profile.detail-row label="Smoking" :value="$profile->smoking" />
                <x-profile.detail-row label="Drinking" :value="$profile->drinking" />
            </dl>
        </section>

        {{-- About --}}
        @if ($profile->about_me)
            <section class="card mt-6 p-6">
                <h2 class="card-title">About</h2>
                <p class="mt-3 text-sm">{{ $profile->about_me }}</p>
            </section>
        @endif
    </div>
@endsection
