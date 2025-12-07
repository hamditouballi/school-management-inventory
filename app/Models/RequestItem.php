<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestItem extends Model
{
    protected $fillable = ['item_id', 'request_id', 'quantity_requested'];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function request()
    {
        return $this->belongsTo(Request::class);
    }
}
