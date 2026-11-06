@extends('layouts.member')

@section('title', 'Shortlist — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Shortlist</h1>

        <nav class="card mt-6 inline-flex flex-wrap gap-1 p-1">
            <a href="{{ route('shortlist.index') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'mine' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                My shortlist
            </a>
            <a href="{{ route('shortlist.received') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'received' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                Who shortlisted me
            </a>
        </nav>

        @if ($locked)
            <div class="card mt-6 p-6 text-center">
                <p class="text-sm">
                    {{ $total }} {{ \Illuminate\Support\Str::plural('member', $total) }} shortlisted you.
                </p>
                <p class="mt-1 text-sm" style="color:var(--text-muted)">Upgrade your membership to see who they are.</p>
                <a href="{{ route('membership.plans') }}" class="mt-4 inline-block rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
                    Upgrade
                </a>
            </div>
        @elseif ($shortlists->isEmpty())
            <p class="mt-6 text-sm" style="color:var(--text-muted)">
                {{ $tab === 'mine' ? "You haven't shortlisted anyone yet." : 'No one has shortlisted you yet.' }}
            </p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($shortlists as $row)
                    @php $profile = $row->{$relation}?->profile; @endphp
                    @if ($profile)
                        <x-profile.card :profile="$profile" :viewer="$viewer" />
                    @endif
                @endforeach
            </div>

            <div class="mt-6">
                {{ $shortlists->links() }}
            </div>
        @endif
    </div>
@endsection
