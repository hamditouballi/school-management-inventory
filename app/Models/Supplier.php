<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'contact_info', 'notes'];

    public function supplierItems()
    {
        return $this->hasMany(SupplierItem::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'supplier_items')
            ->withPivot('unit_price')
            ->withTimestamps();
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function propositions()
    {
        return $this->hasMany(Proposition::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasManyThrough(
            PurchaseOrderItem::class,
            Proposition::class,
            'supplier_id',
            'proposition_id',
            'id',
            'id'
        );
    }

    public function getAllPurchaseOrdersAttribute()
    {
        $poIds = $this->purchaseOrderItems()
            ->pluck('purchase_order_id')
            ->unique()
            ->values();

        return PurchaseOrder::whereIn('id', $poIds)->get();
    }
}
