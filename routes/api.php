<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
| Split by audience rather than by resource. Middleware, throttles and token
| abilities differ sharply between the three groups, and keeping them in one
| file makes it far too easy to attach a public route to an authenticated
| group by accident.
|
| v1 is a directory (app/Http/Controllers/Api/V1), not a prefix trick — a
| future v2 gets its own controllers and Resources while sharing the Actions,
| Services and Models underneath.
*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1_public.php';
    require __DIR__.'/api_v1_user.php';
    require __DIR__.'/api_v1_admin.php';
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('fcm-token/store', [\App\Http\Controllers\Api\V1\Auth\FcmTokenController::class, 'store']);
    Route::post('fcm-token/remove', [\App\Http\Controllers\Api\V1\Auth\FcmTokenController::class, 'remove']);

    // Part G: Contact details customer approval
    Route::post('contact/request', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'requestAccess']);
    Route::post('contact/respond', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'respond']);
    Route::get('contact/requests/pending', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'pendingRequests']);
    Route::get('contact/requests/approved', [\App\Http\Controllers\Api\V1\User\ContactApprovalController::class, 'approvedRequests']);

    // Part H: Notification sound and channel configuration
    Route::get('notifications/config', [\App\Http\Controllers\Api\V1\User\NotificationConfigController::class, 'show']);
});
