@extends('layouts.auth')

@section('title', 'Create your free profile — '.config('app.name'))
@section('robots', 'noindex, follow')

@section('body')
    <section class="mx-auto max-w-lg px-4 py-12">
        <h1 class="font-serif text-3xl font-bold text-ink-900 sm:text-4xl">Create your free profile</h1>
        <p class="mt-2 text-sm" style="color:var(--text-muted)">
            Takes less than five minutes. You'll verify your mobile number next.
        </p>

        <form method="POST" action="{{ route('register') }}" id="register-form" enctype="multipart/form-data" class="mt-8 space-y-5">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium">Full name</label>
                <input id="name" name="name" type="text" required minlength="2" maxlength="120"
                       value="{{ old('name') }}"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div>
                <label for="mobile" class="block text-sm font-medium">Mobile number</label>
                <input id="mobile" name="mobile" type="tel" required maxlength="16"
                       value="{{ old('mobile') }}"
                       placeholder="98765 43210"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                @error('mobile')
                    <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs" style="color:var(--text-muted)">Your primary contact mobile number.</p>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email <span style="color:var(--text-muted)">(optional)</span></label>
                <input id="email" name="email" type="email" maxlength="190"
                       value="{{ old('email') }}"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <div class="relative mt-1">
                        <input id="password" name="password" type="password" required minlength="8"
                               class="w-full rounded-md border px-3 py-2 pr-10 text-sm" style="border-color:var(--border-default)"
                               autocomplete="new-password">
                        <button type="button" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                                data-target="password" aria-label="Toggle password visibility">
                            <svg class="eye-open h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg class="eye-closed hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="mt-1 text-xs text-danger">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium">Confirm</label>
                    <div class="relative mt-1">
                        <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8"
                               class="w-full rounded-md border px-3 py-2 pr-10 text-sm" style="border-color:var(--border-default)"
                               autocomplete="new-password">
                        <button type="button" class="toggle-password-btn absolute inset-y-0 right-0 flex items-center pr-3 text-gray-500 hover:text-gray-700 focus:outline-none"
                                data-target="password_confirmation" aria-label="Toggle confirm password visibility">
                            <svg class="eye-open h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <svg class="eye-closed hidden h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.243 4.243L9.88 9.88" />
                            </svg>
                        </button>
                    </div>
                    <div id="confirm-feedback" class="mt-1 hidden text-xs font-medium"></div>
                </div>
            </div>

            {{-- Password requirements dynamic checklist --}}
            <div id="password-checklist-card" class="rounded-md border p-3 text-xs" style="border-color:var(--border-default);background-color:var(--bg-surface)">
                <p id="checklist-title" class="font-medium text-gray-700">Password must contain:</p>
                <ul id="checklist-items" class="mt-1.5 space-y-1 text-gray-600">
                    <li id="req-length" class="flex items-center gap-1.5 text-danger">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                        Must be at least 8 characters
                    </li>
                    <li id="req-upper" class="flex items-center gap-1.5 text-danger">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                        At least 1 uppercase letter (A-Z)
                    </li>
                    <li id="req-lower" class="flex items-center gap-1.5 text-danger">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                        At least 1 lowercase letter (a-z)
                    </li>
                    <li id="req-number" class="flex items-center gap-1.5 text-danger">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                        At least 1 number (0-9)
                    </li>
                    <li id="req-special" class="flex items-center gap-1.5 text-danger">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-danger"></span>
                        At least 1 special character (!@#$%^&*...)
                    </li>
                </ul>
                <div id="checklist-success" class="hidden font-medium text-success">
                    ✓ Password meets all requirements!
                </div>
            </div>

            <div>
                <span class="block text-sm font-medium">I am</span>
                <div class="mt-1 flex gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="gender" value="male" required @checked(old('gender') === 'male')> Male
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="radio" name="gender" value="female" required @checked(old('gender') === 'female')> Female
                    </label>
                </div>
            </div>

            <div>
                <label for="date_of_birth" class="block text-sm font-medium">Date of birth</label>
                <input id="date_of_birth" name="date_of_birth" type="date" required
                       value="{{ old('date_of_birth') }}"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            {{-- Profile Photo Upload (Groom / Bride) --}}
            <div class="rounded-lg border p-4" style="border-color:var(--border-default);background-color:var(--bg-surface)">
                <div class="flex items-center justify-between">
                    <div>
                        <label id="photo-label" for="photo" class="block text-sm font-medium text-ink-900">
                            Profile Photo <span style="color:var(--text-muted);font-weight:normal">(optional)</span>
                        </label>
                        <p id="photo-sublabel" class="text-xs" style="color:var(--text-muted)">
                            Groom or Bride profile photo (JPG, PNG, WEBP, max 5MB)
                        </p>
                    </div>
                    <span class="rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-700">
                        Optional
                    </span>
                </div>

                <div class="mt-3 flex items-center gap-4">
                    {{-- Preview thumbnail or placeholder --}}
                    <div id="photo-preview-wrapper" class="relative flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-dashed border-gray-300 bg-gray-50">
                        <img id="photo-preview-img" src="" alt="Preview" class="hidden h-full w-full object-cover">
                        <div id="photo-placeholder-icon" class="flex flex-col items-center justify-center text-gray-400">
                            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z" />
                            </svg>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex-1">
                        <label for="photo" class="inline-flex cursor-pointer items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50">
                            <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.233-2.33 3 3 0 013.758 3.848A3.752 3.752 0 0118 19.5H6.75z" />
                            </svg>
                            <span id="photo-btn-text">Choose Photo</span>
                        </label>
                        <button type="button" id="photo-remove-btn" class="ml-2 hidden text-xs font-medium text-danger hover:underline">
                            Remove
                        </button>
                        <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp,image/jpg" class="sr-only">
                        <p id="photo-filename" class="mt-1.5 truncate text-xs text-gray-500"></p>
                    </div>
                </div>

                @error('photo')
                    <p class="mt-2 text-xs text-danger">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="profile_created_by" class="block text-sm font-medium">This profile is being created by</label>
                <select id="profile_created_by" name="profile_created_by" required
                        class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
                    @foreach (['self' => 'Myself', 'parent' => 'Parent', 'sibling' => 'Sibling', 'relative' => 'Relative', 'friend' => 'Friend'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('profile_created_by') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="referral_code" class="block text-sm font-medium">Referral code <span style="color:var(--text-muted)">(optional)</span></label>
                <input id="referral_code" name="referral_code" type="text" maxlength="20"
                       value="{{ old('referral_code') }}"
                       class="mt-1 w-full rounded-md border px-3 py-2 text-sm" style="border-color:var(--border-default)">
            </div>

            <button type="submit" class="w-full rounded-md bg-primary-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-600">
                Create account
            </button>
        </form>

        <p class="mt-6 text-center text-sm" style="color:var(--text-muted)">
            Already have an account?
            <a href="{{ route('login') }}" class="font-medium text-primary-600">Log in</a>
        </p>
    </section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Password Eye Toggle
    document.querySelectorAll('.toggle-password-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var targetId = btn.getAttribute('data-target');
            var input = document.getElementById(targetId);
            if (!input) return;

            var eyeOpen = btn.querySelector('.eye-open');
            var eyeClosed = btn.querySelector('.eye-closed');

            if (input.type === 'password') {
                input.type = 'text';
                if (eyeOpen) eyeOpen.classList.add('hidden');
                if (eyeClosed) eyeClosed.classList.remove('hidden');
            } else {
                input.type = 'password';
                if (eyeOpen) eyeOpen.classList.remove('hidden');
                if (eyeClosed) eyeClosed.classList.add('hidden');
            }
        });
    });

    // 2. Real-time Password Checklist & Confirm Matching
    var passwordInput = document.getElementById('password');
    var confirmInput = document.getElementById('password_confirmation');
    var reqLength = document.getElementById('req-length');
    var reqUpper = document.getElementById('req-upper');
    var reqLower = document.getElementById('req-lower');
    var reqNumber = document.getElementById('req-number');
    var reqSpecial = document.getElementById('req-special');
    var checklistTitle = document.getElementById('checklist-title');
    var checklistSuccess = document.getElementById('checklist-success');
    var confirmFeedback = document.getElementById('confirm-feedback');

    function checkPassword() {
        var val = passwordInput.value || '';

        var hasLength = val.length >= 8;
        var hasUpper = /[A-Z]/.test(val);
        var hasLower = /[a-z]/.test(val);
        var hasNumber = /[0-9]/.test(val);
        var hasSpecial = /[^A-Za-z0-9]/.test(val);

        // When a rule is satisfied, hide it from the reminder list
        reqLength.classList.toggle('hidden', hasLength);
        reqUpper.classList.toggle('hidden', hasUpper);
        reqLower.classList.toggle('hidden', hasLower);
        reqNumber.classList.toggle('hidden', hasNumber);
        reqSpecial.classList.toggle('hidden', hasSpecial);

        var allValid = hasLength && hasUpper && hasLower && hasNumber && hasSpecial;

        if (allValid) {
            checklistTitle.classList.add('hidden');
            checklistSuccess.classList.remove('hidden');
        } else {
            checklistTitle.classList.remove('hidden');
            checklistSuccess.classList.add('hidden');
        }

        checkConfirm();
    }

    function checkConfirm() {
        if (!confirmInput.value) {
            confirmFeedback.classList.add('hidden');
            return;
        }

        confirmFeedback.classList.remove('hidden');
        if (confirmInput.value === passwordInput.value) {
            confirmFeedback.textContent = '✓ Passwords match';
            confirmFeedback.className = 'mt-1 text-xs font-medium text-success';
        } else {
            confirmFeedback.textContent = 'Passwords do not match';
            confirmFeedback.className = 'mt-1 text-xs font-medium text-danger';
        }
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', checkPassword);
    }
    if (confirmInput) {
        confirmInput.addEventListener('input', checkConfirm);
    }

    // 3. Photo Upload Preview & Dynamic Gender Label
    var photoInput = document.getElementById('photo');
    var photoPreviewImg = document.getElementById('photo-preview-img');
    var photoPlaceholderIcon = document.getElementById('photo-placeholder-icon');
    var photoBtnText = document.getElementById('photo-btn-text');
    var photoRemoveBtn = document.getElementById('photo-remove-btn');
    var photoFilename = document.getElementById('photo-filename');
    var photoLabel = document.getElementById('photo-label');
    var photoSublabel = document.getElementById('photo-sublabel');

    function updatePhotoGenderLabel() {
        var selectedGender = document.querySelector('input[name="gender"]:checked');
        if (!photoLabel || !photoSublabel) return;

        if (selectedGender && selectedGender.value === 'male') {
            photoLabel.innerHTML = 'Groom\'s Profile Photo (மணமகன் புகைப்படம்) <span style="color:var(--text-muted);font-weight:normal">(optional)</span>';
            photoSublabel.textContent = 'Upload a clear profile photo of the groom (JPG, PNG, WEBP, max 5MB)';
        } else if (selectedGender && selectedGender.value === 'female') {
            photoLabel.innerHTML = 'Bride\'s Profile Photo (மணமகள் புகைப்படம்) <span style="color:var(--text-muted);font-weight:normal">(optional)</span>';
            photoSublabel.textContent = 'Upload a clear profile photo of the bride (JPG, PNG, WEBP, max 5MB)';
        } else {
            photoLabel.innerHTML = 'Profile Photo (மணமகன் / மணமகள் புகைப்படம்) <span style="color:var(--text-muted);font-weight:normal">(optional)</span>';
            photoSublabel.textContent = 'Groom or Bride profile photo (JPG, PNG, WEBP, max 5MB)';
        }
    }

    document.querySelectorAll('input[name="gender"]').forEach(function (radio) {
        radio.addEventListener('change', updatePhotoGenderLabel);
    });
    updatePhotoGenderLabel();

    if (photoInput) {
        photoInput.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (file) {
                // Check size (5MB = 5 * 1024 * 1024)
                if (file.size > 5 * 1024 * 1024) {
                    alert('The selected image is larger than 5MB. Please choose a smaller file.');
                    this.value = '';
                    return;
                }

                var reader = new FileReader();
                reader.onload = function (e) {
                    photoPreviewImg.src = e.target.result;
                    photoPreviewImg.classList.remove('hidden');
                    photoPlaceholderIcon.classList.add('hidden');
                    photoBtnText.textContent = 'Change Photo';
                    photoRemoveBtn.classList.remove('hidden');
                    photoFilename.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (photoRemoveBtn) {
        photoRemoveBtn.addEventListener('click', function () {
            photoInput.value = '';
            photoPreviewImg.src = '';
            photoPreviewImg.classList.add('hidden');
            photoPlaceholderIcon.classList.remove('hidden');
            photoBtnText.textContent = 'Choose Photo';
            photoRemoveBtn.classList.add('hidden');
            photoFilename.textContent = '';
        });
    }
});
</script>
@endpush
