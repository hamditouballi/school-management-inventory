<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PurchaseOrderNeedsInitialApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $creatorName = $this->purchaseOrder->responsibleStock->name;

        return [
            'type' => 'po_needs_initial_approval',
            'purchase_order_id' => $this->purchaseOrder->id,
            'creator_name' => $creatorName,
            'message' => __('messages.notification_po_needs_initial_approval', [
                'name' => $creatorName,
                'id' => $this->purchaseOrder->id,
            ]),
            'url' => '/purchase-orders',
        ];
    }
}
