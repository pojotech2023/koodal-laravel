<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Actions\Verification\SubmitVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Verification\SubmitVerificationRequest;
use App\Http\Resources\VerificationDocumentResource;
use App\Models\User;
use App\Services\Profile\ProfileCompletion;
use App\Services\Verification\AadhaarOtpRequests;
use App\Services\Verification\AadhaarVerificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * The member's own verification state, and the accepted document types in
     * the same response.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $documents = $user->verificationDocuments()->latest()->get();
        $isVerified = (bool) ($user->profile?->is_verified || $user->is_aadhaar_verified);

        return ApiResponse::success([
            'is_verified' => $isVerified,
            'is_aadhaar_verified' => (bool) $user->is_aadhaar_verified,
            'aadhaar_verified_at' => $user->aadhaar_verified_at?->toIso8601String(),
            'aadhaar_number_masked' => $user->aadhaar_number ? 'XXXX XXXX ' . substr($user->aadhaar_number, -4) : null,
            'verified_badge' => $isVerified ? [
                'verified' => true,
                'label' => 'Verified Profile',
                'icon' => '✓',
            ] : null,
            'documents' => VerificationDocumentResource::collection($documents)->resolve($request),
            'accepted_types' => collect(config('matrimony.verification.types'))
                ->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
                ->values()
                ->all(),
        ], 'Verification status retrieved successfully');
    }

    public function store(SubmitVerificationRequest $request, SubmitVerification $submitVerification): JsonResponse
    {
        $document = $submitVerification(
            user: $request->user(),
            type: $request->string('type')->toString(),
            document: $request->file('document'),
            documentNumber: $request->input('document_number'),
        );

        return ApiResponse::created(
            new VerificationDocumentResource($document),
            'Document submitted. Our team will review it within 48 hours.',
        );
    }

    /**
     * Generate Aadhaar verification OTP for authenticated member.
     */
    public function generateAadhaarOtp(Request $request, AadhaarVerificationService $service, AadhaarOtpRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'id_number' => ['required', 'string'],
        ]);

        $cleanAadhaar = preg_replace('/\D/', '', $validated['id_number']);

        if (strlen($cleanAadhaar) !== 12) {
            return ApiResponse::error('Please provide a valid 12-digit Aadhaar number.', [
                'id_number' => ['The Aadhaar number must be exactly 12 digits.'],
            ], 422);
        }

        // Check if already linked to another user
        $exists = User::where('aadhaar_number', $cleanAadhaar)
            ->where('id', '!=', $request->user()->id)
            ->exists();

        if ($exists) {
            return ApiResponse::error('This Aadhaar number is already linked to another account.', [
                'id_number' => ['This Aadhaar number has already been registered.'],
            ], 422);
        }

        $result = $service->generateOtp($cleanAadhaar);

        if (! $result['success']) {
            return ApiResponse::error($result['message'], [], 400);
        }

        $requestId = (string) $result['request_id'];
        $requests->remember(AadhaarOtpRequests::memberKey($request->user()->id), $requestId, $cleanAadhaar);

        return ApiResponse::success([
            'request_id' => $requestId,
            'id_number' => $cleanAadhaar,
            'expires_in' => 600,
        ], $result['message']);
    }

    /**
     * Submit OTP to complete Aadhaar verification for authenticated member.
     *
     * Only for the request this member started with generateAadhaarOtp(), and
     * only for the number that request was for: the OTP proves who holds the
     * phone linked to that one number. `id_number` is accepted for older
     * clients, but must match.
     */
    public function verifyAadhaarOtp(Request $request, AadhaarOtpRequests $requests, ProfileCompletion $completion): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'string'],
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
            'id_number' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $key = AadhaarOtpRequests::memberKey($user->id);
        $pending = $requests->find($key, (string) $validated['request_id']);

        if (! $pending) {
            return ApiResponse::error(AadhaarOtpRequests::EXPIRED, ['otp' => [AadhaarOtpRequests::EXPIRED]], 422);
        }

        $cleanAadhaar = $pending['aadhaar'];

        if (! $requests->claims($pending, $validated['id_number'] ?? null)) {
            return ApiResponse::error(AadhaarOtpRequests::OTHER_NUMBER, ['id_number' => [AadhaarOtpRequests::OTHER_NUMBER]], 422);
        }

        // Checked again here, not only when the OTP was sent: another account
        // may have verified this number in between.
        if (User::where('aadhaar_number', $cleanAadhaar)->where('id', '!=', $user->id)->exists()) {
            $requests->forget($key);

            return ApiResponse::error(AadhaarOtpRequests::LINKED, [
                'id_number' => ['This Aadhaar number has already been registered.'],
            ], 422);
        }

        $result = $requests->submit($key, $pending, $validated['otp']);

        if (! $result['success']) {
            return ApiResponse::error($result['message'], $result['status'] === 422 ? ['otp' => [$result['message']]] : [], $result['status']);
        }

        // Update user record with verified Aadhaar
        $user->forceFill([
            'aadhaar_number' => $cleanAadhaar,
            'is_aadhaar_verified' => true,
            'aadhaar_verified_at' => now(),
        ])->save();

        if ($user->profile) {
            $user->profile->update(['is_verified' => true]);
        }

        // The stored percentage is what the dashboard and search read; the
        // website's wizard recalculates here too.
        $completion->recalculate($user);

        return ApiResponse::success([
            'is_verified' => true,
            'is_aadhaar_verified' => true,
            'aadhaar_verified_at' => $user->aadhaar_verified_at?->toIso8601String(),
            'aadhaar_number_masked' => 'XXXX XXXX ' . substr($cleanAadhaar, -4),
            'verified_badge' => [
                'verified' => true,
                'label' => 'Verified Profile',
                'icon' => '✓',
            ],
            'full_name' => $result['data']['full_name'] ?? null,
            'gender' => $result['data']['gender'] ?? null,
            'dob' => $result['data']['dob'] ?? null,
        ], 'Aadhaar verified successfully! ✓');
    }
}
