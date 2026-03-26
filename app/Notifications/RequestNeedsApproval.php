<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RequestNeedsApproval extends Notification implements ShouldQueue
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
        $requesterName = $this->requestModel->user->name;

        return [
            'type' => 'request_needs_approval',
            'request_id' => $this->requestModel->id,
            'requester_name' => $requesterName,
            'message' => __('messages.notification_new_request_pending', [
                'name' => $requesterName,
                'id' => $this->requestModel->id,
            ]),
            'url' => '/requests',
        ];
    }
}
