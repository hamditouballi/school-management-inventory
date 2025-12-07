<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = ['supplier', 'date', 'id_responsible_finance', 'id_purchase_order', 'id_purchase_order_item', 'file_path', 'image_path'];

    protected $appends = ['total_amount'];

    public function getTotalAmountAttribute()
    {
        return $this->invoiceItems->sum('subtotal');
    }

    protected $casts = [
        'date' => 'date',
    ];

    public function responsibleFinance()
    {
        return $this->belongsTo(User::class, 'id_responsible_finance');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'id_purchase_order');
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'id_purchase_order_item');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
