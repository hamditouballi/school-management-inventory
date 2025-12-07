<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'item_name', 'description', 'quantity', 'unit', 'unit_price', 'image_path'];

    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->unit_price;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
