<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class StockManagerNewApprovedRequest extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RequestModel $requestModel,
        public string $hrManagerName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_approved_request',
            'request_id' => $this->requestModel->id,
            'hr_manager_name' => $this->hrManagerName,
            'message' => __('messages.notification_new_approved_request', [
                'hr_name' => $this->hrManagerName,
                'id' => $this->requestModel->id,
            ]),
            'url' => '/requests',
        ];
    }
}
