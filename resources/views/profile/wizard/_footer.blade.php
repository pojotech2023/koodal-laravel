{{-- Shared Back/Continue row — included inside each step's own <form> (Back
     is a plain link, so it doesn't submit anything sitting inside one) so
     the buttons align on one row without the shell needing to know each
     step's form structure. Relies on $step/$steps/$position from
     profile/wizard.blade.php, which @include shares automatically. --}}
<div class="mt-6 flex justify-between gap-3">
    @if ($position > 0)
        <a href="{{ route('profile.wizard.step', $steps[$position - 1]) }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
            Back
        </a>
    @else
        <span></span>
    @endif

    @if ($step === 'photos')
        <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Continue to Jadhagam &rarr;
        </a>
    @elseif ($step === 'jadhagam')
        <a href="{{ route('profile.wizard.step', 'aadhaar') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Continue to Aadhaar verify &rarr;
        </a>
    @elseif ($step === 'aadhaar')
        <a href="{{ route('profile.wizard.step', 'preferences') }}" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Continue to Preference &rarr;
        </a>
    @else
        <button type="submit" class="rounded-md bg-primary-500 px-4 py-2 text-sm font-medium text-white hover:bg-primary-600">
            Continue
        </button>
    @endif
</div>
