@extends('admin.layouts.admin')

@section('title', 'Review document — '.config('app.name').' Admin')

@section('admin-body')
    @php($member = $document->user)

    <a href="{{ route('admin.verification.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; ID verification</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">
                {{ $types[$document->type] ?? ucfirst($document->type) }} —
                <a href="{{ route('admin.users.show', $document->user_id) }}" class="text-primary-600 hover:underline">{{ $member?->name }}</a>
            </h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                <span class="font-mono">{{ $member?->profile_code }}</span> ·
                {{ ucfirst($member?->gender ?? '') }} · {{ $member?->age() }} yrs ·
                Submitted {{ $document->created_at->format('d M Y, H:i') }}
            </p>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-sm font-medium',
            'bg-warning/10 text-warning' => in_array($document->status, ['pending', 'resubmit'], true),
            'bg-success/10 text-success' => $document->status === 'approved',
            'bg-danger/10 text-danger' => $document->status === 'rejected',
        ])>{{ ucfirst($document->status) }}</span>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="card-title">Document</h2>
                <p class="text-xs" style="color:var(--text-muted)">
                    This link expires {{ $ttl }} minutes after the page loaded. Reload the page to mint a new one — each reload is audited.
                </p>
            </div>

            <div class="mt-3 overflow-hidden rounded-md border" style="border-color:var(--border-default)">
                @if ($document->mime_type === 'application/pdf')
                    {{-- <object>, not <img>: a PDF ID scan is common and an
                         image tag renders it as a broken icon with no hint
                         that the file is fine. The link below is the fallback
                         for a browser with no inline PDF viewer. --}}
                    <object data="{{ $url }}" type="application/pdf" class="h-[70vh] w-full">
                        <p class="p-4 text-sm">
                            This browser cannot display the PDF inline.
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline">Open it in a new tab</a>.
                        </p>
                    </object>
                @else
                    <a href="{{ $url }}" target="_blank" rel="noopener">
                        <img src="{{ $url }}" alt="Verification document submitted by {{ $member?->profile_code }}" class="max-h-[70vh] w-full object-contain">
                    </a>
                @endif
            </div>

            <p class="mt-3 text-xs" style="color:var(--text-muted)">
                Do not save, screenshot or forward this file. Your name is on the audit row for this view.
            </p>
        </section>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="card-title">Check against</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <x-profile.detail-row label="Name on account" :value="$member?->name" />
                    <x-profile.detail-row label="Email" :value="$member?->email" />
                    <x-profile.detail-row label="Mobile" :value="$member?->mobile" />
                    <x-profile.detail-row label="Account status" :value="ucfirst($member?->status ?? '—')" />
                    <x-profile.detail-row label="File type" :value="$document->mime_type" />
                    <x-profile.detail-row label="File size" :value="$document->size_bytes ? round($document->size_bytes / 1024).' KB' : null" />
                </dl>
                <p class="mt-3 text-xs" style="color:var(--text-muted)">
                    The document number is stored only as a hash — it cannot be shown here, and it is not what a review turns on.
                </p>
            </section>

            @if ($document->isPending())
                {{-- Three outcomes, not two. "Resubmit" exists because a
                     blurred or cropped scan is a fixable problem, and
                     rejecting it reads to the member as "your ID is not
                     valid" — see ReviewVerification's docblock. Remarks are
                     mandatory on both non-approve paths (the FormRequest
                     enforces it by reading the {action} route segment); the
                     suggestions below fill the field rather than replace it,
                     because a reviewer needs to say *which* name mismatched. --}}
                <section class="card p-5">
                    <h2 class="card-title">Decision</h2>

                    <form method="POST" action="{{ route('admin.verification.action', [$document, 'approve']) }}" class="mt-3">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="w-full rounded-md bg-success px-4 py-2 text-sm font-medium text-white hover:opacity-90">
                            Approve — grant the verified badge
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.verification.action', [$document, 'resubmit']) }}" class="mt-4 space-y-2"
                          x-data="{ remarks: '' }">
                        @csrf
                        @method('PATCH')

                        <label for="remarks" class="block text-sm font-medium">Remarks to the member</label>
                        <textarea id="remarks" name="remarks" x-model="remarks" required minlength="5" maxlength="255" rows="3"
                                  placeholder="What was wrong with the document, in a sentence they can act on"
                                  class="w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">{{ old('remarks') }}</textarea>

                        <div class="flex flex-wrap gap-1">
                            @foreach ($remarkSuggestions as $suggestion)
                                <button type="button" x-on:click="remarks = @js($suggestion).concat(remarks ? ' — ' + remarks : '')"
                                        class="rounded-full border px-2 py-0.5 text-xs" style="border-color:var(--border-default)">
                                    {{ $suggestion }}
                                </button>
                            @endforeach
                        </div>

                        <button type="submit" class="w-full rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                            Ask for a clearer copy
                        </button>

                        {{-- Same remarks field, different endpoint. `formaction`
                             keeps one textarea for both outcomes — two
                             textareas would let a reviewer type into one and
                             submit the other, empty. --}}
                        <button type="submit" formaction="{{ route('admin.verification.action', [$document, 'reject']) }}"
                                class="w-full rounded-md border border-danger px-4 py-2 text-sm font-medium text-danger hover:bg-danger/10">
                            Reject the document
                        </button>
                    </form>
                </section>
            @else
                <section class="card p-5">
                    <h2 class="card-title">Decision</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <x-profile.detail-row label="Outcome" :value="ucfirst($document->status)" />
                        <x-profile.detail-row label="Reviewed by" :value="$document->reviewer?->name" />
                        <x-profile.detail-row label="Reviewed at" :value="$document->reviewed_at?->format('d M Y, H:i')" />
                        <x-profile.detail-row label="Remarks" :value="$document->remarks" />
                    </dl>
                    <p class="mt-3 text-xs" style="color:var(--text-muted)">
                        A reviewed document cannot be re-decided. Resubmission is the member's path back to the queue.
                    </p>
                </section>
            @endif
        </div>
    </div>
@endsection
