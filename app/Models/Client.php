<?php

namespace App\Models;

use App\Rules\ValidRut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'rut',
        'name',
        'address',
        'region',
        'commune',
        'phone',
        'email',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function repairOrders(): HasMany
    {
        return $this->hasMany(RepairOrder::class);
    }

    public function dataRecoveryCases(): HasMany
    {
        return $this->hasMany(DataRecoveryCase::class);
    }

    public function getFormattedRutAttribute(): string
    {
        return ValidRut::format($this->rut);
    }
}
