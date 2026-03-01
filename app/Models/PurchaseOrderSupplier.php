<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderSupplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'supplier_name',
        'price',
        'quality_rating',
        'notes',
        'is_selected'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_selected' => 'boolean'
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
