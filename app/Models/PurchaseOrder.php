<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['date', 'id_responsible_stock', 'status', 'supplier', 'total_amount'];

    protected $casts = [
        'date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function responsibleStock()
    {
        return $this->belongsTo(User::class, 'id_responsible_stock');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }



    public function propositions()
    {
        return $this->hasMany(Proposition::class);
    }

    public function propositionGroups()
    {
        return $this->hasMany(PropositionGroup::class)->with('propositions.supplier', 'item')->orderBy('proposition_order');
    }

    public function bonDeLivraisons()
    {
        return $this->hasMany(BonDeLivraison::class);
    }

    public function getTotalAmountAttribute(): float
    {
        return $this->purchaseOrderItems->sum(function ($item) {
            return $item->init_quantity * ($item->unit_price ?? 0);
        });
    }
}
