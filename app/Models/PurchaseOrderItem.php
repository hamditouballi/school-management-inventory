<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;
    protected $fillable = ['item_id', 'purchase_order_id', 'quantity', 'unit_price', 'image_path', 'new_item_name'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_purchase_order_item');
    }
}
