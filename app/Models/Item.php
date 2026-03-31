<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = ['designation', 'description', 'quantity', 'unit', 'low_stock_threshold', 'image_path', 'category_id'];

    protected $appends = ['is_low_stock'];

    public function getIsLowStockAttribute(): bool
    {
        return $this->quantity < $this->low_stock_threshold;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function requestItems()
    {
        return $this->hasMany(RequestItem::class);
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function bonDeSorties()
    {
        return $this->hasMany(BonDeSortie::class);
    }

    public function supplierItems()
    {
        return $this->hasMany(SupplierItem::class);
    }
}
