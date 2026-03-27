<?php

namespace App\Notifications;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PurchaseOrderFinalApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public string $approverName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'po_final_approved',
            'purchase_order_id' => $this->purchaseOrder->id,
            'approver_name' => $this->approverName,
            'supplier' => $this->purchaseOrder->supplier,
            'message' => __('messages.notification_po_final_approved', [
                'name' => $this->approverName,
                'id' => $this->purchaseOrder->id,
                'supplier' => $this->purchaseOrder->supplier,
            ]),
            'url' => '/purchase-orders',
        ];
    }
}
