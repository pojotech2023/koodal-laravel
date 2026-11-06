<?php

use App\Http\Controllers\Api\V1\Auth\AadhaarOtpController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use App\Http\Controllers\Api\V1\HealthController;
use App\Http\Controllers\Api\V1\User\ChatQuickRepliesController;
use App\Http\Controllers\Api\V1\User\CmsController;
use App\Http\Controllers\Api\V1\User\LocationController;
use App\Http\Controllers\Api\V1\User\MasterDataController;
use App\Http\Controllers\Api\V1\User\MembershipController;
use App\Http\Controllers\Api\V1\Webhook\RazorpayWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health & app version
|--------------------------------------------------------------------------
| /app/version is the only remote kill switch we have — it lets us block
| builds with a known payment or security bug at launch, so it is tested
| every release.
*/

Route::get('health', HealthController::class);
Route::get('app/version', [HealthController::class, 'appVersion']);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Tight throttles: these are the endpoints worth brute-forcing. The OTP
| bucket is keyed on the identifier rather than the IP, because an attacker
| rotating IPs against one mobile number is the actual threat.
*/

Route::middleware('throttle:auth')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/login/otp', [AuthController::class, 'requestOtpLogin']);
    Route::post('auth/forgot-password', [PasswordResetController::class, 'sendResetOtp']);
    Route::post('auth/reset-password', [PasswordResetController::class, 'reset']);
});

Route::middleware('throttle:otp')->group(function () {
    Route::post('auth/verify-otp', [OtpController::class, 'verify']);
    Route::post('auth/resend-otp', [OtpController::class, 'resend']);
    Route::post('auth/aadhaar/generate-otp', [AadhaarOtpController::class, 'generateOtp']);
    Route::post('auth/aadhaar/verify-otp', [AadhaarOtpController::class, 'verifyOtp']);
});

/*
|--------------------------------------------------------------------------
| Master data & locations
|--------------------------------------------------------------------------
| Public and heavily cached. Every payload carries a `version` string so the
| mobile app can skip the download when nothing has changed.
|
| Nothing here may ever be hardcoded in a client.
*/

Route::middleware('cache.headers:public;max_age=86400')->group(function () {
    Route::get('master-data', MasterDataController::class);
    Route::get('master-data/{type}', [MasterDataController::class, 'byType'])
        ->where('type', 'religions|communities|sub-communities|mother-tongues|educations|occupations|income-ranges|marital-statuses|lifestyle-options|castes|gothras');

    Route::get('locations/countries', [LocationController::class, 'countries']);
    Route::get('locations/states', [LocationController::class, 'states']);
    Route::get('locations/cities', [LocationController::class, 'cities']);
});

/*
|--------------------------------------------------------------------------
| Public content
|--------------------------------------------------------------------------
*/

Route::get('membership/plans', [MembershipController::class, 'plans']);
Route::get('chat/default-messages', ChatQuickRepliesController::class);
Route::get('cms/pages/{slug}', [CmsController::class, 'page']);
Route::get('cms/faqs', [CmsController::class, 'faqs']);
Route::get('cms/banners', [CmsController::class, 'banners']);
Route::get('cms/success-stories', [CmsController::class, 'successStories']);
Route::post('cms/contact', [CmsController::class, 'contact'])->middleware('throttle:5,10');
Route::get('settings/public', [CmsController::class, 'publicSettings']);

/*
|--------------------------------------------------------------------------
| Razorpay webhook
|--------------------------------------------------------------------------
| No auth and no CSRF — authenticity comes from the HMAC signature, which the
| controller verifies before touching anything. This is the authoritative
| payment path: a user who force-quits mid-payment is activated here.
|
| Always returns 200 once the signature is valid, including for duplicates.
| Returning an error makes Razorpay retry indefinitely.
*/

Route::post('webhooks/razorpay', RazorpayWebhookController::class)
    ->middleware('throttle:300,1');
