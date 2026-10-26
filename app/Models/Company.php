<?php

namespace App\Models;

use App\Rules\ValidRut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rut',
        'address',
        'phone',
        'email',
        'mail_from_name',
        'mail_from_address',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_security',
        'groq_api_key',
        'groq_model',
        'transport_api_url',
        'transport_api_key',
        'diagnostic_fee_gross',
        'technical_diagnostic_hours',
        'recovery_diagnostic_hours',
        'plan',
        'is_active',
    ];

    protected $casts = [
        'groq_api_key' => 'encrypted',
        'smtp_password' => 'encrypted',
        'transport_api_key' => 'encrypted',
        'smtp_port' => 'integer',
        'diagnostic_fee_gross' => 'decimal:2',
        'technical_diagnostic_hours' => 'integer',
        'recovery_diagnostic_hours' => 'integer',
        'is_active' => 'boolean',
    ];

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
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

    public function homeServicePreorders(): HasMany
    {
        return $this->hasMany(HomeServicePreorder::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function getFormattedRutAttribute(): string
    {
        return ValidRut::format($this->rut);
    }
}
