<?php

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Verification\AadhaarOtpRequests;
use App\Services\Verification\AadhaarVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Aadhaar OTP before registration. A success puts `aadhaar_verified` and the
 * number in the session, which `RegisterUser` turns into a verified account —
 * so the OTP must be for a request THIS browser started, and the number saved
 * is the one that request was sent for, never one posted later.
 */
class AadhaarVerificationController extends Controller
{
    public function generateOtp(Request $request, AadhaarVerificationService $service, AadhaarOtpRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'id_number' => ['required', 'string', 'regex:/^[0-9\s]{12,14}$/'],
        ]);

        $cleanAadhaar = preg_replace('/\D/', '', $validated['id_number']);

        if (strlen($cleanAadhaar) !== 12) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide a valid 12-digit Aadhaar number.',
            ], 422);
        }

        if (User::where('aadhaar_number', $cleanAadhaar)->exists()) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::LINKED], 422);
        }

        $result = $service->generateOtp($cleanAadhaar);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        $requestId = (string) $result['request_id'];
        $requests->remember(AadhaarOtpRequests::requestKey($requestId), $requestId, $cleanAadhaar);

        $request->session()->put('aadhaar_pending_number', $cleanAadhaar);
        $request->session()->put('aadhaar_pending_request_id', $requestId);

        return response()->json([
            'success' => true,
            'request_id' => $requestId,
            'message' => $result['message'],
        ]);
    }

    public function verifyOtp(Request $request, AadhaarOtpRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'request_id' => ['required', 'string'],
            'otp' => ['required', 'string', 'min:6', 'max:6'],
            'aadhaar_number' => ['nullable', 'string'],
        ]);

        $requestId = (string) $validated['request_id'];
        $key = AadhaarOtpRequests::requestKey($requestId);
        $startedHere = hash_equals((string) $request->session()->get('aadhaar_pending_request_id', ''), $requestId);
        $pending = $startedHere ? $requests->find($key, $requestId) : null;

        if (! $pending) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::EXPIRED], 422);
        }

        if (! $requests->claims($pending, $validated['aadhaar_number'] ?? null)) {
            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::OTHER_NUMBER], 422);
        }

        $cleanAadhaar = $pending['aadhaar'];

        // Checked again here: an account may have registered it since.
        if (User::where('aadhaar_number', $cleanAadhaar)->exists()) {
            $requests->forget($key);

            return response()->json(['success' => false, 'message' => AadhaarOtpRequests::LINKED], 422);
        }

        $result = $requests->submit($key, $pending, $validated['otp']);

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $result['status']);
        }

        $request->session()->forget(['aadhaar_pending_number', 'aadhaar_pending_request_id']);
        $request->session()->put('aadhaar_verified', true);
        $request->session()->put('aadhaar_number', $cleanAadhaar);
        $request->session()->put('aadhaar_verified_at', now()->toIso8601String());
        $request->session()->put('aadhaar_data', $result['data']);

        return response()->json([
            'success' => true,
            'message' => 'Aadhaar verified successfully! ✓',
            'full_name' => $result['data']['full_name'] ?? null,
            'gender' => $result['data']['gender'] ?? null,
            'dob' => $result['data']['dob'] ?? null,
        ]);
    }
}
