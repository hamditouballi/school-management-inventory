<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PropositionGroup extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'purchase_order_id',
        'item_id',
        'proposition_order',
    ];

    protected $casts = [
        'proposition_order' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function propositions(): HasMany
    {
        return $this->hasMany(Proposition::class, 'proposition_group_id');
    }

    public function getTotalQuantityAttribute(): float
    {
        return $this->propositions->sum('quantity');
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->propositions->sum(fn ($p) => $p->quantity * $p->unit_price);
    }
}
