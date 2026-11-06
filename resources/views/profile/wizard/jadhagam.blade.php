@php $jadhagams = $user->jadhagams; @endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-semibold">Upload Jadhagam (Horoscope)</h2>
            <p class="text-sm mt-0.5" style="color:var(--text-muted)">
                Upload your horoscope / Jadhagam chart image (Rasi &amp; Navamsam). It will be verified by our team before being displayed.
            </p>
        </div>

        <form method="POST" action="{{ route('profile.jadhagam.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
            @csrf
            <input type="file" name="jadhagam" accept="image/jpeg,image/png,image/webp" required class="text-sm">
            <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600 transition">
                Upload Jadhagam
            </button>
        </form>
    </div>

    @if (session('status'))
        <div class="mt-4 rounded-md border border-success bg-success/10 p-3 text-sm font-medium text-success">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->has('jadhagam'))
        <div class="mt-4 rounded-md border border-danger bg-danger/10 p-3 text-sm font-medium text-danger">
            {{ $errors->first('jadhagam') }}
        </div>
    @endif

    @if ($jadhagams->isEmpty())
        <div class="mt-8 rounded-lg border-2 border-dashed p-8 text-center" style="border-color:var(--border-default)">
            <svg class="mx-auto h-12 w-12" style="color:var(--text-muted)" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium">No Jadhagam uploaded yet</h3>
            <p class="mt-1 text-xs" style="color:var(--text-muted)">
                Members who attach their Jadhagam receive faster responses from matching families.
            </p>
        </div>
    @else
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($jadhagams as $jadhagam)
                <div class="card overflow-hidden">
                    <div class="relative aspect-[3/4] bg-neutral-100 overflow-hidden">
                        <a href="{{ $jadhagam->url() }}" target="_blank" title="Click to view full size">
                            <img src="{{ $jadhagam->url() }}" alt="Jadhagam" class="h-full w-full object-contain hover:scale-105 transition duration-200">
                        </a>
                        
                        <span @class([
                            'absolute top-2 right-2 rounded-full px-2.5 py-1 text-xs font-semibold shadow',
                            'bg-warning text-white' => $jadhagam->approval_status === 'pending',
                            'bg-success text-white' => $jadhagam->approval_status === 'approved',
                            'bg-danger text-white' => $jadhagam->approval_status === 'rejected',
                        ])>
                            {{ ucfirst($jadhagam->approval_status) }}
                        </span>
                    </div>

                    <div class="p-3 space-y-2">
                        @if ($jadhagam->approval_status === 'pending')
                            <p class="text-xs text-warning font-medium">
                                &#9203; Under admin review. It will appear on your profile once approved.
                            </p>
                        @elseif ($jadhagam->approval_status === 'approved')
                            <p class="text-xs text-success font-medium">
                                &#10003; Approved! Visible on your matrimony profile.
                            </p>
                        @elseif ($jadhagam->approval_status === 'rejected')
                            <div class="rounded bg-danger/10 p-2 text-xs text-danger">
                                <span class="font-semibold">Rejected:</span> {{ $jadhagam->rejection_reason ?: 'Image unclear or invalid.' }}
                            </div>
                        @endif

                        <div class="flex items-center justify-between border-t pt-2" style="border-color:var(--border-default)">
                            <a href="{{ $jadhagam->url() }}" target="_blank" class="text-xs font-medium text-primary-600 hover:underline">
                                View full image &rarr;
                            </a>

                            <form method="POST" action="{{ route('profile.jadhagam.destroy', $jadhagam) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" onclick="return confirm('Delete this Jadhagam image?')" class="rounded border border-danger/40 px-2 py-1 text-xs font-medium text-danger hover:bg-danger/10">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('profile.wizard._footer')
</div>
