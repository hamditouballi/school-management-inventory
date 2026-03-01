<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'status', 'dateCreated', 'image_path'];

    protected $casts = [
        'dateCreated' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function requestItems()
    {
        return $this->hasMany(RequestItem::class);
    }

    public function bonDeSorties()
    {
        return $this->hasMany(BonDeSortie::class);
    }
}
