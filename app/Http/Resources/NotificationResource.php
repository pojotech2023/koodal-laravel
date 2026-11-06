<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'body' => $this->body,

            // The deep link the tap handler follows, resolved through the model
            // so a row written before a route changed still lands somewhere
            // real. This is what keeps `Get.toNamed(data.route)` a one-liner
            // instead of a switch the app has to ship to extend.
            'route' => $this->route(),
            'data' => $this->data,

            'is_read' => $this->isRead(),
            'read_at' => $this->read_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
