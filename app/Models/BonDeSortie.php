<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonDeSortie extends Model
{
    use HasFactory;
    protected $fillable = ['request_id', 'item_id', 'quantity', 'date', 'id_responsible_stock'];

    protected $casts = [
        'date' => 'date',
    ];

    public function request()
    {
        return $this->belongsTo(Request::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function responsibleStock()
    {
        return $this->belongsTo(User::class, 'id_responsible_stock');
    }
}
