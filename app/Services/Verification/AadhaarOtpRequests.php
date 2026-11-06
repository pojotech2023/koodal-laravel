<?php

namespace App\Services\Verification;

use Illuminate\Support\Facades\Cache;

/**
 * The Aadhaar OTP requests that have been started and not yet finished, for
 * every flow that verifies Aadhaar: the member's profile (website wizard and
 * API) and registration (website and API).
 *
 * An OTP proves who holds the phone linked to ONE Aadhaar number, so the
 * verify step must use the request this caller started and the number that
 * request was for — never a request id or number the client sends later.
 * Each request also allows only a few wrong OTPs before it has to be started
 * again, so it cannot be guessed through.
 */
class AadhaarOtpRequests
{
    public const TTL_MINUTES = 15;

    public const MAX_ATTEMPTS = 5;

    public const EXPIRED = 'This OTP has expired. Please request a new OTP.';

    public const OTHER_NUMBER = 'This OTP was sent for a different Aadhaar number. Please request a new OTP.';

    public const TOO_MANY = 'Too many incorrect OTPs. Please request a new OTP.';

    public const LINKED = 'This Aadhaar number is already linked to another account.';

    public function __construct(protected AadhaarVerificationService $service) {}

    /** A signed-in member's request — one at a time, from the website or the app. */
    public static function memberKey(int $userId): string
    {
        return "user_{$userId}_aadhaar_req";
    }

    /** A registration request, before there is an account to key it on. */
    public static function requestKey(string $requestId): string
    {
        return "aadhaar_req_{$requestId}";
    }

    public function remember(string $key, string $requestId, string $aadhaar): void
    {
        $expiresAt = now()->addMinutes(self::TTL_MINUTES);

        Cache::put($key, [
            'request_id' => $requestId,
            'aadhaar' => $aadhaar,
            'attempts' => 0,
            'expires_at' => $expiresAt->getTimestamp(),
        ], $expiresAt);
    }

    /**
     * The pending request under `$key`, if it is the one `$requestId` names.
     *
     * @return array{request_id: string, aadhaar: string, attempts: int, expires_at: int}|null
     */
    public function find(string $key, string $requestId): ?array
    {
        $pending = Cache::get($key);

        if (! is_array($pending) || ! isset($pending['request_id'], $pending['aadhaar'], $pending['expires_at'])) {
            return null;
        }

        return hash_equals((string) $pending['request_id'], $requestId) ? $pending : null;
    }

    /** Whether a number the client also sent (older clients do) is the one the OTP went to. */
    public function claims(array $pending, ?string $number): bool
    {
        return blank($number) || preg_replace('/\D/', '', $number) === $pending['aadhaar'];
    }

    /**
     * Checks the OTP with the provider for `$pending`'s request. Success ends
     * the request; a wrong OTP counts against it, and the last one allowed
     * ends it too.
     *
     * @return array{success: bool, status: int, message: string, data: array}
     */
    public function submit(string $key, array $pending, string $otp): array
    {
        $result = $this->service->submitOtp($pending['request_id'], $otp);

        if ($result['success']) {
            $this->forget($key);

            return ['success' => true, 'status' => 200, 'message' => $result['message'], 'data' => $result['data'] ?? []];
        }

        if (! $this->recordFailure($key, $pending)) {
            return ['success' => false, 'status' => 422, 'message' => self::TOO_MANY, 'data' => []];
        }

        return ['success' => false, 'status' => 400, 'message' => $result['message'], 'data' => []];
    }

    public function forget(string $key): void
    {
        Cache::forget($key);
    }

    /** Counts a wrong OTP. Returns false once the request is used up and gone. */
    protected function recordFailure(string $key, array $pending): bool
    {
        $pending['attempts'] = (int) ($pending['attempts'] ?? 0) + 1;

        if ($pending['attempts'] >= self::MAX_ATTEMPTS) {
            $this->forget($key);

            return false;
        }

        Cache::put($key, $pending, now()->setTimestamp($pending['expires_at']));

        return true;
    }
}
