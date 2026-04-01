<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonDeLivraison extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'date',
        'file_path',
        'notes',
        'id_responsible_stock',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function responsibleStock()
    {
        return $this->belongsTo(User::class, 'id_responsible_stock');
    }

    public function items()
    {
        return $this->hasMany(BonDeLivraisonItem::class);
    }
}
