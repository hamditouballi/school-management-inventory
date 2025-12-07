<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = ['date', 'id_responsible_stock', 'status', 'supplier', 'total_amount'];

    protected $casts = [
        'date' => 'date',
    ];

    public function responsibleStock()
    {
        return $this->belongsTo(User::class, 'id_responsible_stock');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
