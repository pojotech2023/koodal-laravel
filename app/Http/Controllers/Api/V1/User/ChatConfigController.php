<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Services\Chat\Contracts\RealtimeTransport;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * What makes one app binary work against either realtime backend: the client
 * asks the server which transport to use and receives the matching credentials,
 * rather than compiling in a choice. Switching is one env var on the server.
 *
 * Deliberately not gated on `can_chat`. The same socket carries the
 * `private-user.{id}` channel — notifications, interest alerts, conversation
 * updates — which every member gets regardless of plan. A free member who
 * cannot connect is a free member whose notification badge never updates.
 */
class ChatConfigController extends Controller
{
    public function __construct(protected RealtimeTransport $transport) {}

    public function __invoke(Request $request): JsonResponse
    {
        return ApiResponse::success(
            $this->transport->clientConfig($request->user()),
            'Chat configuration retrieved successfully',
        );
    }
}
