<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'purchase_order_id',
        'init_quantity',
        'final_quantity',
        'unit_price',
        'image_path',
        'new_item_name',
        'state',
        'approved_by',
        'proposition_id',
    ];

    protected $casts = [
        'init_quantity' => 'decimal:2',
        'final_quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function proposition()
    {
        return $this->belongsTo(Proposition::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'id_purchase_order_item');
    }

    public function getTotalAmountAttribute(): float
    {
        return ($this->final_quantity ?? $this->init_quantity) * ($this->unit_price ?? 0);
    }
}
