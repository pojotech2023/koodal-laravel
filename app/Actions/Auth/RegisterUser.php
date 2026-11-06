<?php

namespace App\Actions\Auth;

use App\Models\MatrimonyLimit;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Support\ProfileCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates the account and every row that must exist alongside it.
 *
 * The profile, preference, privacy and notification rows are created here
 * rather than lazily on first access. A null-object dance in forty call sites
 * is worse than four inserts once, and "user exists but profile does not" is
 * the kind of state that produces 500s at 3 a.m.
 *
 * No token is issued until the OTP is verified — otherwise a typo'd mobile
 * number yields a fully working account nobody can recover.
 */
class RegisterUser
{
    public function __construct(protected OtpService $otp) {}

    /**
     * @return array{user: User, otp_required: bool, otp_expires_in: int}
     */
    public function __invoke(array $data, string $source = 'web', ?string $ip = null): array
    {
        if (! MatrimonyLimit::canRegister()) {
            $limit = MatrimonyLimit::current();
            throw ValidationException::withMessages([
                'mobile' => ["Member registration limit ({$limit->available_user_count} members) has been reached for this platform. Please contact admin to upgrade the plan."],
            ]);
        }

        $isAadhaarVerified = (bool) (session('aadhaar_verified') || ! empty($data['aadhaar_verified']));
        $aadhaarNumber = $data['aadhaar_number'] ?? session('aadhaar_number');

        $user = DB::transaction(function () use ($data, $source, $isAadhaarVerified, $aadhaarNumber) {
            $user = User::create([
                'profile_code' => ProfileCode::generate(),
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'aadhaar_number' => $aadhaarNumber,
                'is_aadhaar_verified' => $isAadhaarVerified,
                'aadhaar_verified_at' => $isAadhaarVerified ? now() : null,
                'mobile' => $data['mobile'],
                'password' => $data['password'],
                'gender' => $data['gender'],
                'date_of_birth' => $data['date_of_birth'],
                'role_id' => Role::where('name', 'user')->value('id'),
                'status' => $isAadhaarVerified ? 'active' : 'pending',
                'mobile_verified_at' => $isAadhaarVerified ? now() : null,
                'registration_source' => $source,
                'referral_code' => $data['referral_code'] ?? null,
            ]);

            $user->profile()->create([
                'profile_created_by' => $data['profile_created_by'] ?? 'self',
                'is_verified' => $isAadhaarVerified,
                'verified_at' => $isAadhaarVerified ? now() : null,
                // Approval is a product toggle: a launch with no moderation
                // team must not leave every profile invisible.
                'approval_status' => config('matrimony.profile.approval_required') ? 'pending' : 'approved',
            ]);

            $user->preferences()->create([]);
            $user->privacySettings()->create(config('matrimony.privacy.defaults'));
            $user->notificationSettings()->create([]);

            return $user;
        });

        session()->forget(['aadhaar_verified', 'aadhaar_number', 'aadhaar_pending_number', 'aadhaar_pending_request_id']);

        MatrimonyLimit::recordUserRegistered();

        if ($isAadhaarVerified || ! config('matrimony.profile.approval_required')) {
            try {
                \App\Jobs\NotifyUsersOfNewProfileJob::dispatch($user->id);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('New profile notification dispatch skipped: '.$e->getMessage());
            }
        }

        if ($isAadhaarVerified) {
            return [
                'user' => $user,
                'otp_required' => false,
                'otp_expires_in' => 0,
            ];
        }

        $otp = $this->otp->issue($user->mobile, 'registration', 'sms', $ip);
        $this->otp->deliver($user->mobile, $otp['code']);

        return [
            'user' => $user,
            'otp_required' => true,
            'otp_expires_in' => $otp['expires_in'],
        ];
    }
}
