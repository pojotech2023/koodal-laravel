{{-- Step 5 — not a `ProfileSectionRequest` like the other four; photos have
     their own resource (Web\ProfileController's upload/setPrimary/destroy
     actions, mirroring the API's ProfilePhotoController). A freshly
     uploaded photo is `pending`, not `approved`, so it does not move the
     completion percentage until a moderator looks at it. --}}
@php $photos = $user->photos; @endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm" style="color:var(--text-muted)">
            A profile with a clear photo gets far more interest. Upload at least one.
        </p>
        <form method="POST" action="{{ route('profile.photos.store') }}" enctype="multipart/form-data" class="flex items-center gap-2">
            @csrf
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" required class="text-sm">
            <button type="submit" class="rounded-md bg-primary-500 px-3 py-1.5 text-sm font-medium text-white hover:bg-primary-600">
                Upload
            </button>
        </form>
    </div>

    @if ($photos->isEmpty())
        <p class="mt-6 text-sm" style="color:var(--text-muted)">
            No photos yet. Photos are reviewed before they go live, so upload one now to get it into the queue.
        </p>
    @else
        <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3">
            @foreach ($photos as $photo)
                <div class="overflow-hidden rounded-lg border" style="border-color:var(--border-default)">
                    <div class="relative aspect-[4/5] bg-neutral-100">
                        <img src="{{ $photo->urlFor('thumbnail') }}" alt="" class="h-full w-full object-cover">
                        @if ($photo->is_primary)
                            <span class="absolute left-2 top-2 rounded-full bg-primary-500 px-2 py-0.5 text-xs text-white">Primary</span>
                        @endif
                        <span class="absolute right-2 top-2 rounded-full bg-black/60 px-2 py-0.5 text-xs text-white">{{ ucfirst($photo->approval_status) }}</span>
                    </div>

                    @if ($photo->approval_status === 'rejected' && $photo->rejection_reason)
                        <p class="bg-danger/10 px-2 py-1 text-xs text-danger">{{ $photo->rejection_reason }}</p>
                    @endif

                    <div class="flex gap-1 p-2">
                        @if (! $photo->is_primary && $photo->approval_status === 'approved')
                            <form method="POST" action="{{ route('profile.photos.primary', $photo) }}">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="rounded-md border px-2 py-1 text-xs font-medium" style="border-color:var(--border-default)">
                                    Set primary
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('profile.photos.destroy', $photo) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-md border border-danger px-2 py-1 text-xs font-medium text-danger">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @include('profile.wizard._footer')
</div>
