<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonDeLivraisonItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bon_de_livraison_id',
        'purchase_order_item_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function bonDeLivraison()
    {
        return $this->belongsTo(BonDeLivraison::class);
    }

    public function purchaseOrderItem()
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
