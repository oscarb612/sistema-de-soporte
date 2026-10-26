<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'client_id',
        'type',
        'brand',
        'model',
        'serial_number',
        'accessories',
        'physical_condition',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function repairOrders(): HasMany
    {
        return $this->hasMany(RepairOrder::class);
    }

    public function linkedRepairOrders(): BelongsToMany
    {
        return $this->belongsToMany(RepairOrder::class)->withTimestamps();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RepairOrderPayment::class);
    }
}
