<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Warehouse extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'location',
        'description',
        'manager',
        'manager_location',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'warehouse_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'warehouse_team');
    }
}
