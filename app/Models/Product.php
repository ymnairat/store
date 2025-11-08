<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'code',
        'category',
        'unit',
        'price',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'product_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'product_team')
            ->withPivot('created_by')
            ->withTimestamps();
    }
}
