<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PurchaseOrderFinalRejected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public string $rejecterName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'po_final_rejected',
            'purchase_order_id' => $this->purchaseOrder->id,
            'rejecter_name' => $this->rejecterName,
            'message' => __('messages.notification_po_final_rejected', [
                'name' => $this->rejecterName,
                'id' => $this->purchaseOrder->id,
            ]),
            'url' => '/purchase-orders',
        ];
    }
}
