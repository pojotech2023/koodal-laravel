@extends('admin.layouts.admin')

@section('title', 'Report #'.$report->id.' — '.config('app.name').' Admin')

@section('admin-body')
    <a href="{{ route('admin.reports.index') }}" class="text-sm font-medium text-primary-600 hover:underline">&larr; Reports</a>

    <div class="mt-3 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl font-bold text-ink-900">
                Report #{{ $report->id }} — {{ $categories[$report->category] ?? ucfirst($report->category) }}
            </h1>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Filed {{ $report->created_at->format('d M Y, H:i') }} · {{ $report->created_at->diffForHumans() }}
                @if ($report->assignee) · Assigned to {{ $report->assignee->name }} @endif
            </p>
        </div>

        <span @class([
            'rounded-full px-3 py-1 text-sm font-medium',
            'bg-warning/10 text-warning' => $report->status === 'open',
            'bg-primary-50 text-primary-700' => $report->status === 'investigating',
            'bg-success/10 text-success' => $report->status === 'action_taken',
            'bg-danger/10 text-danger' => $report->status === 'dismissed',
        ])>{{ str_replace('_', ' ', ucfirst($report->status)) }}</span>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="card p-5">
                <h2 class="card-title">What was reported</h2>
                <p class="mt-3 whitespace-pre-line text-sm">{{ $report->description ?: 'No description given.' }}</p>

                @if ($report->evidence_paths)
                    {{-- One link per file, each one an audited access to a
                         short-lived signed URL — never an inline <img>, which
                         would log a view for a moderator who only scrolled
                         past it, and never a raw path. --}}
                    <h3 class="mt-4 text-xs font-medium uppercase tracking-wide" style="color:var(--text-muted)">
                        Evidence ({{ count($report->evidence_paths) }})
                    </h3>
                    <ul class="mt-2 space-y-1 text-sm">
                        @foreach ($report->evidence_paths as $index => $path)
                            <li>
                                <a href="{{ route('admin.reports.evidence', [$report, $index]) }}" target="_blank" rel="noopener"
                                   class="text-primary-600 hover:underline">
                                    Open evidence file {{ $index + 1 }}
                                </a>
                                <span class="text-xs" style="color:var(--text-muted)">— opening it is recorded against your name</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if ($report->evidence_message_ids)
                    {{-- The ids were snapshotted at report time so the
                         evidence survives the reported member deleting the
                         messages; chat moderation shows deleted messages for
                         exactly that reason. The link carries `report_id`, so
                         the moderator does not have to look the accused
                         account up by hand — and opening a transcript is
                         audited, which is why this is a link rather than an
                         inline quote. --}}
                    <p class="mt-4 text-sm" style="color:var(--text-muted)">
                        {{ count($report->evidence_message_ids) }} chat message(s) were snapshotted as evidence.
                        @can('moderate', \App\Models\Conversation::class)
                            <a href="{{ route('admin.chat.index', ['report_id' => $report->id]) }}" class="text-primary-600 hover:underline">
                                Open the conversations
                            </a> between these two.
                        @else
                            Reading them needs the <span class="font-mono">chat.moderate</span> permission.
                        @endcan
                    </p>
                @endif
            </section>

            <section class="card p-5">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 class="card-title">Other reports against this account</h2>
                    <span @class([
                        'rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-danger/10 text-danger' => $report->reports_against_count >= 3,
                        'bg-warning/10 text-warning' => $report->reports_against_count === 2,
                    ])>{{ $report->reports_against_count }} in total</span>
                </div>

                @if ($related->isEmpty())
                    <p class="mt-3 text-sm" style="color:var(--text-muted)">This is the only report against this account.</p>
                @else
                    <ul class="mt-3 divide-y text-sm" style="border-color:var(--border-default)">
                        @foreach ($related as $other)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                <span>
                                    <a href="{{ route('admin.reports.show', $other) }}" class="text-primary-600 hover:underline">#{{ $other->id }}</a>
                                    · {{ $categories[$other->category] ?? ucfirst($other->category) }}
                                    · by {{ $other->reporter?->name }}
                                </span>
                                <span class="text-xs" style="color:var(--text-muted)">
                                    {{ str_replace('_', ' ', $other->status) }} · {{ $other->created_at->format('d M Y') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="space-y-6">
            <section class="card p-5">
                <h2 class="card-title">Accused</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-xs" style="color:var(--text-muted)">Member</dt>
                        <dd>
                            <a href="{{ route('admin.users.show', $report->reported_user_id) }}" class="text-primary-600 hover:underline">
                                {{ $report->reportedUser?->name }}
                            </a>
                            <span class="font-mono text-xs" style="color:var(--text-muted)">{{ $report->reportedUser?->profile_code }}</span>
                        </dd>
                    </div>
                    <x-profile.detail-row label="Account status" :value="ucfirst($report->reportedUser?->status ?? '—')" />
                </dl>

                <h2 class="mt-4 text-sm font-semibold">Reporter</h2>
                <dl class="mt-3 space-y-2 text-sm">
                    <div>
                        <dt class="text-xs" style="color:var(--text-muted)">Member</dt>
                        <dd>
                            <a href="{{ route('admin.users.show', $report->reporter_id) }}" class="text-primary-600 hover:underline">
                                {{ $report->reporter?->name }}
                            </a>
                            <span class="font-mono text-xs" style="color:var(--text-muted)">{{ $report->reporter?->profile_code }}</span>
                        </dd>
                    </div>
                    <x-profile.detail-row label="Account status" :value="ucfirst($report->reporter?->status ?? '—')" />
                </dl>
            </section>

            @if ($report->isOpen())
                @can('assign', $report)
                    <section class="card p-5">
                        <h2 class="card-title">Assignment</h2>
                        {{-- No assignee picker: `assignee_id` is optional and
                             defaults to the caller, and "take this one" is the
                             action a moderator performs a hundred times to
                             every one reassignment. Reassigning to a named
                             colleague stays an API call until a module needs
                             it in the panel. --}}
                        <form method="POST" action="{{ route('admin.reports.assign', $report) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="w-full rounded-md border border-primary-500 px-4 py-2 text-sm font-medium text-primary-600 hover:bg-primary-50">
                                {{ $report->assignee ? 'Take over this report' : 'Assign to me' }}
                            </button>
                        </form>
                    </section>
                @endcan

                @can('resolve', $report)
                    <section class="card p-5">
                        <h2 class="card-title">Resolve</h2>
                        <p class="mt-1 text-xs" style="color:var(--text-muted)">
                            The sanction is applied to the accused account as part of this write, and the reporter is told the report was handled.
                        </p>

                        <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" class="mt-3 space-y-3">
                            @csrf

                            <x-form.select name="action_taken" label="Action taken" required
                                :options="collect($actions)->map(fn ($a) => ['id' => $a, 'name' => match ($a) {
                                    'warned' => 'Warn the member',
                                    'suspended' => 'Suspend the account',
                                    'blocked' => 'Block the account',
                                    default => 'No action warranted',
                                }])->all()" />

                            <div>
                                <label for="note" class="block text-sm font-medium">Resolution note</label>
                                <textarea id="note" name="note" rows="4" minlength="10" maxlength="1000"
                                          placeholder="What you found and why this outcome. Someone will read this on appeal."
                                          class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">{{ old('note') }}</textarea>
                                <p class="mt-1 text-xs" style="color:var(--text-muted)">Required for every outcome except “no action warranted”.</p>
                            </div>

                            <button type="submit" class="w-full rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                                Resolve report
                            </button>
                        </form>
                    </section>

                    {{-- Deliberately its own form and its own heading rather
                         than a fourth option in the picker above: `dismissed`
                         (the report was not actionable — duplicate,
                         retaliatory) and `action_taken = none` (investigated,
                         nothing warranted) are counted separately when judging
                         whether a category is working. Merging them in the UI
                         would merge them in the data. --}}
                    <section class="card p-5">
                        <h2 class="card-title">Dismiss</h2>
                        <p class="mt-1 text-xs" style="color:var(--text-muted)">
                            Nothing happens to either account. Use this when the report itself is not actionable — a duplicate, or filed in retaliation.
                        </p>

                        <form method="POST" action="{{ route('admin.reports.dismiss', $report) }}" class="mt-3 space-y-3">
                            @csrf
                            <div>
                                <label for="dismiss-note" class="block text-sm font-medium">Note <span class="font-normal" style="color:var(--text-muted)">(optional)</span></label>
                                <textarea id="dismiss-note" name="note" rows="2" maxlength="1000"
                                          class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)"></textarea>
                            </div>
                            <button type="submit" class="w-full rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                                Dismiss as not actionable
                            </button>
                        </form>
                    </section>
                @endcan
            @else
                <section class="card p-5">
                    <h2 class="card-title">Outcome</h2>
                    <dl class="mt-3 space-y-2 text-sm">
                        <x-profile.detail-row label="Status" :value="str_replace('_', ' ', ucfirst($report->status))" />
                        <x-profile.detail-row label="Action taken" :value="$report->action_taken ? ucfirst($report->action_taken) : null" />
                        <x-profile.detail-row label="Resolved by" :value="$report->resolver?->name" />
                        <x-profile.detail-row label="Resolved at" :value="$report->resolved_at?->format('d M Y, H:i')" />
                        <x-profile.detail-row label="Note" :value="$report->resolution_note" />
                    </dl>
                </section>
            @endif
        </div>
    </div>
@endsection
