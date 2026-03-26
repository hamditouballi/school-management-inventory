<?php

namespace App\Notifications;

use App\Models\Request as RequestModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OtherHrApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RequestModel $requestModel,
        public string $approverName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'other_hr_approved',
            'request_id' => $this->requestModel->id,
            'approver_name' => $this->approverName,
            'message' => __('messages.notification_other_hr_approved', [
                'hr_name' => $this->approverName,
                'id' => $this->requestModel->id,
            ]),
            'url' => '/requests',
        ];
    }
}
