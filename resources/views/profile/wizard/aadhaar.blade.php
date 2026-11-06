{{-- Step 7 — Aadhaar Verification.
     Verifies user's Aadhaar via QuickEKYC OTP API.
     Upon successful verification, displays the green verified badge and awards 100% profile strength. --}}
@php
    $isVerified = (bool) $user->is_aadhaar_verified;
    $maskedAadhaar = $user->aadhaar_number_masked 
        ?? ($user->aadhaar_number ? 'XXXX XXXX ' . substr($user->aadhaar_number, -4) : null);
@endphp

<div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-b pb-4" style="border-color:var(--border-default)">
        <div>
            <h2 class="text-xl font-bold text-ink-900 flex items-center gap-2">
                <span>Aadhaar Identity Verification</span>
                <span id="aadhaar-badge-verified" class="{{ $isVerified ? '' : 'hidden' }} inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-bold text-emerald-800 border border-emerald-300">
                    <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>✓ Verified</span>
                </span>
            </h2>
            <p class="mt-1 text-sm" style="color:var(--text-muted)">
                Verify your Aadhaar card number with OTP to get the verified badge and reach <strong>100% profile strength</strong>.
            </p>
        </div>
    </div>

    {{-- Already Verified State --}}
    <div id="aadhaar-verified-card" class="{{ $isVerified ? '' : 'hidden' }} mt-6 rounded-xl border border-emerald-300 bg-emerald-50/40 p-6 text-ink-900">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white shadow-sm">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <div class="flex-1">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-lg font-bold text-emerald-950 flex items-center gap-2">
                        <span>Aadhaar Successfully Verified</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-0.5 text-xs font-semibold text-white">✓ 100% Complete</span>
                    </h3>
                </div>
                <p class="mt-1 text-sm text-emerald-800">
                    Your identity has been verified via Government Aadhaar E-KYC. Your profile now displays the trusted <strong>✓ Verified</strong> badge to all members.
                </p>

                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 text-xs">
                    <div class="rounded-lg border border-emerald-200 bg-white p-3">
                        <span class="text-ink-500 block">Verified Aadhaar Number</span>
                        <span id="aadhaar-verified-number" class="font-mono text-sm font-bold text-ink-900 tracking-wider">
                            {{ $maskedAadhaar ?? 'XXXX XXXX 0328' }}
                        </span>
                    </div>
                    <div class="rounded-lg border border-emerald-200 bg-white p-3">
                        <span class="text-ink-500 block">Verified Date & Time</span>
                        <span id="aadhaar-verified-time" class="text-sm font-semibold text-ink-900">
                            {{ $user->aadhaar_verified_at ? $user->aadhaar_verified_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-between gap-3 border-t border-emerald-200 pt-4">
            <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="rounded-md border px-4 py-2 text-sm font-medium bg-white hover:bg-neutral-50" style="border-color:var(--border-default)">
                &larr; Back to Jadhagam
            </a>
            <div class="flex gap-2">
                <a href="{{ route('profile.show') }}" class="rounded-md border px-4 py-2 text-sm font-medium bg-white hover:bg-neutral-50" style="border-color:var(--border-default)">
                    View My Profile
                </a>
                <a href="{{ route('profile.wizard.step', 'preferences') }}" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                    Continue to Preference &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Unverified Input State --}}
    <div id="aadhaar-form-card" class="{{ $isVerified ? 'hidden' : '' }} mt-6">
        <div class="rounded-xl border p-5 transition-all" style="border-color:var(--border-default);background-color:var(--bg-surface)" id="aadhaar-box">
            <label for="aadhaar_input_display" class="block text-sm font-semibold text-ink-900">
                12-digit Aadhaar card number <span class="text-danger">*</span>
            </label>
            <p class="mt-0.5 text-xs" style="color:var(--text-muted)">
                We will send a 6-digit OTP to the mobile number registered with your Aadhaar card.
            </p>

            <div class="mt-3 flex flex-wrap gap-2 sm:flex-nowrap">
                <div class="relative flex-1">
                    <input id="aadhaar_input_display" type="text" maxlength="14" placeholder="XXXX XXXX XXXX"
                           value="{{ $user->aadhaar_number ?? '' }}"
                           class="w-full rounded-md border px-3 py-2.5 font-mono text-base tracking-wider transition focus:border-primary-500 focus:outline-none"
                           style="border-color:var(--border-default);background-color:var(--bg-surface)">
                    <span id="aadhaar-input-tick" class="hidden absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-emerald-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </div>
                <button type="button" id="btn-request-otp" class="rounded-md bg-primary-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700 disabled:opacity-50 flex items-center gap-1.5 whitespace-nowrap">
                    <span>Send OTP</span>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
            
            <p id="aadhaar-field-error" class="mt-2 hidden text-xs font-medium text-danger"></p>

            {{-- Only where the test OTPs actually work (QUICKEKYC_TEST_MODE) — never on the live site. --}}
            @if (config('services.quickekyc.test_mode'))
            <div class="mt-3 rounded-lg border border-primary-200 bg-primary-50/50 p-3 text-xs text-primary-900 flex items-center gap-2">
                <svg class="h-4 w-4 text-primary-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>For testing, enter any valid 12-digit number (e.g. <code>8858 5678 0328</code>) and use test OTP: <strong class="font-mono text-primary-950">603810</strong> or <strong class="font-mono text-primary-950">123456</strong>.</span>
            </div>
            @endif

            {{-- OTP Drawer Section --}}
            <div id="otp-drawer" class="mt-4 hidden rounded-lg border border-primary-300 bg-primary-50/70 p-4 transition-all">
                <div class="flex items-center justify-between">
                    <label for="wizard_aadhaar_otp" class="block text-xs font-bold text-ink-900 uppercase tracking-wider">
                        Enter 6-Digit OTP Code
                    </label>
                    <span id="wizard-timer" class="text-xs text-primary-700 font-semibold"></span>
                </div>
                <p class="mt-0.5 text-xs text-ink-600">
                    An OTP was sent to your Aadhaar-registered mobile. Enter it below to verify.
                </p>

                <div class="mt-2 flex flex-wrap gap-2">
                    <input id="wizard_aadhaar_otp" type="text" maxlength="6" placeholder="••••••"
                           class="w-40 rounded-md border border-primary-300 bg-white px-3 py-2 text-center font-mono text-lg font-bold tracking-widest text-ink-900 focus:border-primary-500 focus:outline-none shadow-sm">
                    <button type="button" id="btn-submit-otp" class="rounded-md bg-emerald-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 disabled:opacity-50">
                        Submit &amp; Verify
                    </button>
                    <button type="button" id="btn-resend-otp" class="hidden rounded-md border border-primary-300 bg-white px-4 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-50">
                        Resend OTP
                    </button>
                </div>
                
                <p id="wizard-otp-error" class="mt-2 hidden text-xs text-danger font-semibold"></p>
            </div>
        </div>

        <div class="mt-6 flex justify-between gap-3">
            <a href="{{ route('profile.wizard.step', 'jadhagam') }}" class="rounded-md border px-4 py-2 text-sm font-medium" style="border-color:var(--border-default)">
                &larr; Back to Jadhagam
            </a>
            <a href="{{ route('profile.wizard.step', 'preferences') }}" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-700">
                Continue to Preference &rarr;
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var aadhaarInput = document.getElementById('aadhaar_input_display');
    var btnRequestOtp = document.getElementById('btn-request-otp');
    var aadhaarError = document.getElementById('aadhaar-field-error');
    var otpDrawer = document.getElementById('otp-drawer');
    var otpInput = document.getElementById('wizard_aadhaar_otp');
    var btnSubmitOtp = document.getElementById('btn-submit-otp');
    var btnResendOtp = document.getElementById('btn-resend-otp');
    var otpError = document.getElementById('wizard-otp-error');
    var timerDisplay = document.getElementById('wizard-timer');

    var verifiedCard = document.getElementById('aadhaar-verified-card');
    var formCard = document.getElementById('aadhaar-form-card');
    var verifiedBadge = document.getElementById('aadhaar-badge-verified');
    var verifiedNumber = document.getElementById('aadhaar-verified-number');
    var verifiedTime = document.getElementById('aadhaar-verified-time');
    var strengthPct = document.getElementById('profile-strength-pct');
    var strengthBar = document.getElementById('profile-strength-bar');

    var currentRequestId = '';
    var timerInterval = null;

    if (aadhaarInput) {
        // Auto-format as XXXX XXXX XXXX
        aadhaarInput.addEventListener('input', function () {
            var digits = this.value.replace(/\D/g, '').substring(0, 12);
            var chunks = [];
            for (var i = 0; i < digits.length; i += 4) {
                chunks.push(digits.substring(i, i + 4));
            }
            this.value = chunks.join(' ');
            if (aadhaarError) aadhaarError.classList.add('hidden');
        });

        // Request OTP
        btnRequestOtp.addEventListener('click', function () {
            var clean = aadhaarInput.value.replace(/\D/g, '');
            if (clean.length !== 12) {
                aadhaarError.textContent = 'Please enter a valid 12-digit Aadhaar number.';
                aadhaarError.classList.remove('hidden');
                aadhaarInput.focus();
                return;
            }

            aadhaarError.classList.add('hidden');
            btnRequestOtp.disabled = true;
            btnRequestOtp.innerHTML = '<span class="inline-block animate-spin mr-1">&#9696;</span> Sending OTP...';

            var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch('{{ route("profile.wizard.aadhaar.generate-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id_number: clean })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btnRequestOtp.disabled = false;
                btnRequestOtp.innerHTML = '<span>Send OTP</span><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>';

                if (data.success) {
                    currentRequestId = data.request_id;
                    otpDrawer.classList.remove('hidden');
                    otpError.classList.add('hidden');
                    aadhaarInput.setAttribute('readonly', 'readonly');
                    btnRequestOtp.classList.add('hidden');
                    otpInput.value = '';
                    otpInput.focus();
                    startCountdown(60);
                } else {
                    aadhaarError.textContent = data.message || 'Could not send OTP. Please check your Aadhaar number.';
                    aadhaarError.classList.remove('hidden');
                }
            })
            .catch(function () {
                btnRequestOtp.disabled = false;
                btnRequestOtp.innerHTML = '<span>Send OTP</span>';
                aadhaarError.textContent = 'Verification service unreachable. Please try again.';
                aadhaarError.classList.remove('hidden');
            });
        });

        // Submit OTP
        btnSubmitOtp.addEventListener('click', function () {
            var otpVal = otpInput.value.replace(/\D/g, '');
            if (otpVal.length !== 6) {
                otpError.textContent = 'Please enter the 6-digit OTP code.';
                otpError.classList.remove('hidden');
                otpInput.focus();
                return;
            }

            otpError.classList.add('hidden');
            btnSubmitOtp.disabled = true;
            btnSubmitOtp.innerHTML = '<span class="inline-block animate-spin mr-1">&#9696;</span> Verifying...';

            var clean = aadhaarInput.value.replace(/\D/g, '');
            var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch('{{ route("profile.wizard.aadhaar.verify-otp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    request_id: currentRequestId,
                    otp: otpVal,
                    aadhaar_number: clean
                })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                btnSubmitOtp.disabled = false;
                btnSubmitOtp.innerHTML = 'Submit &amp; Verify';

                if (data.success) {
                    clearInterval(timerInterval);

                    // 1. Instantly update profile strength to 100%!
                    if (strengthPct) strengthPct.textContent = '100%';
                    if (strengthBar) strengthBar.style.width = '100%';

                    // 2. Show verified badge & card details
                    if (verifiedNumber) verifiedNumber.textContent = data.aadhaar_masked || ('XXXX XXXX ' + clean.substring(8));
                    if (verifiedTime) verifiedTime.textContent = data.verified_at || 'Just now';
                    if (verifiedBadge) verifiedBadge.classList.remove('hidden');

                    formCard.classList.add('hidden');
                    verifiedCard.classList.remove('hidden');
                    verifiedCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else {
                    otpError.textContent = data.message || 'Invalid OTP. Please check and try again.';
                    otpError.classList.remove('hidden');
                }
            })
            .catch(function () {
                btnSubmitOtp.disabled = false;
                btnSubmitOtp.innerHTML = 'Submit &amp; Verify';
                otpError.textContent = 'Verification request failed. Please try again.';
                otpError.classList.remove('hidden');
            });
        });

        if (btnResendOtp) {
            btnResendOtp.addEventListener('click', function () {
                btnRequestOtp.click();
            });
        }
    }

    function startCountdown(seconds) {
        clearInterval(timerInterval);
        btnResendOtp.classList.add('hidden');
        var remaining = seconds;
        timerDisplay.textContent = '(' + remaining + 's)';

        timerInterval = setInterval(function () {
            remaining--;
            if (remaining <= 0) {
                clearInterval(timerInterval);
                timerDisplay.textContent = '';
                btnResendOtp.classList.remove('hidden');
            } else {
                timerDisplay.textContent = '(' + remaining + 's)';
            }
        }, 1000);
    }
});
</script>

