<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'supplier', 'date', 'id_responsible_finance', 'id_purchase_order', 'id_purchase_order_item', 'file_path', 'image_path', 'bon_de_livraison_ids'];

    protected $appends = ['total_amount'];

    public function getTotalAmountAttribute()
    {
        return $this->invoiceItems->sum('subtotal');
    }

    protected $casts = [
        'date' => 'date',
        'bon_de_livraison_ids' => 'array',
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

    public function getBonDeLivraisonsAttribute()
    {
        if (empty($this->attributes['bon_de_livraison_ids'])) {
            return collect([]);
        }

        $ids = is_string($this->attributes['bon_de_livraison_ids'])
            ? json_decode($this->attributes['bon_de_livraison_ids'], true)
            : $this->attributes['bon_de_livraison_ids'];

        if (empty($ids)) {
            return collect([]);
        }

        return BonDeLivraison::with('items.purchaseOrderItem.item', 'items.purchaseOrderItem.proposition.supplier')
            ->whereIn('id', $ids)
            ->get();
    }

    public function getSupplierAttribute(): ?string
    {
        if (! empty($this->attributes['supplier'])) {
            return $this->attributes['supplier'];
        }

        $bdlIds = is_string($this->attributes['bon_de_livraison_ids'] ?? '[]')
            ? json_decode($this->attributes['bon_de_livraison_ids'] ?? '[]', true)
            : ($this->attributes['bon_de_livraison_ids'] ?? []);

        if (empty($bdlIds)) {
            return null;
        }

        $bdls = BonDeLivraison::with('items.purchaseOrderItem.proposition.supplier')
            ->whereIn('id', $bdlIds)
            ->get();

        $suppliers = $bdls->flatMap(function ($bdl) {
            return $bdl->items
                ->pluck('purchaseOrderItem.proposition.supplier.name')
                ->filter()
                ->unique();
        })->values();

        return $suppliers->isEmpty() ? null : $suppliers->implode(', ');
    }
}
