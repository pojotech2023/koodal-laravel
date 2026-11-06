<?php

namespace App\Http\Controllers\Web\Auth;

use App\Actions\Auth\RegisterUser;
use App\Actions\Photo\UploadProfilePhoto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\WebRegisterRequest;
use App\Services\Profile\ProfileCompletion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Step 1 of registration (website guide §5).
 * Uses WebRegisterRequest for web-specific file upload validation while leaving
 * API registration completely untouched.
 */
class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(
        WebRegisterRequest $request,
        RegisterUser $registerUser,
        UploadProfilePhoto $uploadPhoto,
        ProfileCompletion $completion,
    ): RedirectResponse {
        // `source` defaults to 'web' on the action itself — nothing to pass.
        $result = $registerUser($request->validated(), ip: $request->ip());
        $user = $result['user'];

        // If the user uploaded a groom/bride profile photo, store it as primary photo
        if ($request->hasFile('photo')) {
            try {
                $uploadPhoto($user, $request->file('photo'));
                $completion->recalculate($user);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Registration photo upload skipped: '.$e->getMessage(), [
                    'user_id' => $user->id,
                ]);
            }
        }

        // If Aadhaar is verified, the user is already authenticated with government identity.
        // Log in directly without requiring a second verification step.
        if (! $result['otp_required'] || $user->is_aadhaar_verified) {
            \Illuminate\Support\Facades\Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard')
                ->with('status', 'Registration successful! Your profile has been created with a verified Aadhaar badge.');
        }

        // If not verified, proceed to OTP verification screen
        $request->session()->put('otp.identifier', $user->mobile);
        $request->session()->put('otp.purpose', 'registration');

        return redirect()->route('otp.verify')
            ->with('status', 'Account created. Enter the code sent to your mobile.');
    }
}