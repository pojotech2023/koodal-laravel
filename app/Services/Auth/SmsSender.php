<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS delivery.
 *
 * India requires every transactional template to be pre-registered under DLT.
 * An unregistered body is dropped by the operator *silently* — no error, no
 * bounce — so the template id travels with every send and the body is built
 * from the registered wording rather than composed ad hoc at the call site.
 *
 * The `log` driver is the local/testing default: no gateway account is needed
 * to develop or run the suite.
 */
class SmsSender
{
    public function send(string $to, string $message, ?string $templateId = null): bool
    {
        $driver = config('services.sms.driver', 'log');

        if ($driver === 'log') {
            Log::channel('security')->info('SMS (log driver)', ['to' => $to, 'message' => $message]);

            return true;
        }

        if ($driver === '2factor' || $driver === 'twofactor') {
            return $this->sendVia2Factor($to, $message, $templateId);
        }

        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->retry(2, 200)
                ->asForm()
                ->post(config('services.sms.endpoint'), [
                    'apikey' => config('services.sms.api_key'),
                    'sender' => config('services.sms.sender_id'),
                    'numbers' => ltrim($to, '+'),
                    'message' => $message,
                    'template_id' => $templateId ?? config('services.sms.template_id'),
                ]);

            if ($response->failed()) {
                Log::channel('security')->error('SMS send failed', [
                    'to' => $to,
                    'status' => $response->status(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // A failed SMS must never take down registration. The caller
            // surfaces "resend" and the user tries again.
            Log::channel('security')->error('SMS transport error', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }

    protected function sendVia2Factor(string $to, string $message, ?string $templateId = null): bool
    {
        $apiKey = config('services.sms.api_key');
        $cleanPhone = substr(preg_replace('/\D/', '', $to), -10);

        try {
            preg_match('/\b\d{4,6}\b/', $message, $matches);
            $otpCode = $matches[0] ?? null;

            $tpl = $templateId ?? config('services.sms.template_id') ?: 'OTP1';

            if ($otpCode) {
                $endpoint = "https://2factor.in/API/V1/{$apiKey}/SMS/{$cleanPhone}/{$otpCode}/{$tpl}";
            } else {
                $endpoint = "https://2factor.in/API/V1/{$apiKey}/SMS/{$cleanPhone}/AUTOGEN/{$tpl}";
            }

            $response = Http::withoutVerifying()->timeout(5)->retry(1, 100)->get($endpoint);

            if ($response->failed() || (isset($response->json()['Status']) && $response->json()['Status'] !== 'Success')) {
                Log::channel('security')->error('2Factor SMS send failed', [
                    'to' => $to,
                    'phone' => $cleanPhone,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            // The 2Factor session id in `response` is what their delivery
            // report is looked up by. The code itself is never logged.
            Log::channel('security')->info('2Factor SMS sent successfully', [
                'to' => $to,
                'phone' => $cleanPhone,
                'response' => $response->json(),
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::channel('security')->error('2Factor SMS transport error', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
