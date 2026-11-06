@extends('layouts.member')

@section('title', 'Profile Views — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="font-serif text-3xl font-bold text-ink-900">Profile Views</h1>
                <p class="mt-1 text-sm" style="color:var(--text-muted)">
                    {{ $tab === 'received' ? 'Members who recently visited your profile' : 'Profiles you recently visited' }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-1.5 rounded-lg border px-3.5 py-2 text-xs font-semibold transition-colors hover:bg-primary-50" style="border-color:var(--border-default)">
                &larr; Back to Dashboard
            </a>
        </div>

        <nav class="card mt-6 inline-flex flex-wrap gap-1 p-1">
            <a href="{{ route('profile-views.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'received' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                Who viewed me
            </a>
            <a href="{{ route('profile-views.sent') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'sent' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                Profiles I viewed
            </a>
        </nav>

        @if ($views->isEmpty())
            <div class="card mt-6 p-8 text-center">
                <p class="text-sm" style="color:var(--text-muted)">
                    {{ $tab === 'received' ? 'No one has viewed your profile yet.' : "You haven't viewed any profiles yet." }}
                </p>
                <a href="{{ route('matches.recommended') }}" class="mt-4 inline-block rounded-lg bg-primary-500 px-4 py-2 text-xs font-semibold text-white hover:bg-primary-600">
                    Browse Matches
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($views as $row)
                    @php $profile = $row->{$relation}?->profile; @endphp
                    @if ($profile)
                        <div class="flex flex-col">
                            <x-profile.card :profile="$profile" :viewer="$viewer" />
                            @if (!empty($row->last_viewed_at))
                                <p class="mt-1.5 px-1 text-xs" style="color:var(--text-muted)">
                                    Viewed {{ \Carbon\Carbon::parse($row->last_viewed_at)->diffForHumans() }}
                                    @if (($row->view_count ?? 1) > 1)
                                        · {{ $row->view_count }} visits
                                    @endif
                                </p>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-6">
                {{ $views->links() }}
            </div>
        @endif
    </div>
@endsection
