@extends('layouts.member')

@section('title', ucfirst($tab).' interests — '.config('app.name'))

@section('body')
    <div class="mx-auto max-w-6xl px-4 py-8">
        <h1 class="font-serif text-3xl font-bold text-ink-900">Interests</h1>

        <nav class="card mt-6 inline-flex flex-wrap gap-1 p-1">
            <a href="{{ route('interests.received') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'received' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                Received
            </a>
            <a href="{{ route('interests.sent') }}" class="rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-150 {{ $tab === 'sent' ? 'bg-primary-500 text-white shadow-sm' : 'hover:bg-primary-50 hover:text-primary-600' }}">
                Sent
            </a>
        </nav>

        @if ($interests->isEmpty())
            <p class="mt-6 text-sm" style="color:var(--text-muted)">
                {{ $tab === 'received' ? "No one has sent you an interest yet." : "You haven't sent any interests yet." }}
            </p>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($interests as $interest)
                    @php $profile = $interest->{$relation}?->profile; @endphp
                    @if ($profile)
                        <div>
                            <x-profile.card :profile="$profile" :viewer="$viewer" />

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <p class="text-xs" style="color:var(--text-muted)">
                                    {{ ucfirst($interest->status) }} · {{ $interest->created_at->diffForHumans() }}
                                </p>

                                @if ($tab === 'received' && $interest->isPending())
                                    <div class="flex gap-2">
                                        <form method="POST" action="{{ route('interests.accept', $interest) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md bg-primary-500 px-3 py-1.5 text-xs font-medium text-white hover:bg-primary-600">Accept</button>
                                        </form>
                                        <form method="POST" action="{{ route('interests.decline', $interest) }}">
                                            @csrf
                                            <button type="submit" class="rounded-md border px-3 py-1.5 text-xs font-medium" style="border-color:var(--border-default)">Decline</button>
                                        </form>
                                    </div>
                                @elseif ($tab === 'sent' && $interest->isPending())
                                    <form method="POST" action="{{ route('interests.cancel', $interest) }}">
                                        @csrf
                                        <button type="submit" class="rounded-md border px-3 py-1.5 text-xs font-medium" style="border-color:var(--border-default)">Cancel</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>

            <div class="mt-6">
                {{ $interests->links() }}
            </div>
        @endif
    </div>
@endsection
