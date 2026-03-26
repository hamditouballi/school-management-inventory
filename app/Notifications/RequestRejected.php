<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RequestRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RequestModel $requestModel
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'request_rejected',
            'request_id' => $this->requestModel->id,
            'message' => __('messages.notification_request_rejected', [
                'id' => $this->requestModel->id,
            ]),
            'url' => '/requests',
        ];
    }
}
