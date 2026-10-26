<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairOrderPayment extends Model
{
    use HasFactory;

    public const TYPES = [
        'abono' => 'Abono',
        'pago_final' => 'Pago final',
        'otro' => 'Otro',
    ];

    public const METHODS = [
        'transferencia' => 'Transferencia',
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'company_id',
        'repair_order_id',
        'device_id',
        'repair_quote_id',
        'created_by',
        'payment_type',
        'payment_method',
        'amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function repairQuote(): BelongsTo
    {
        return $this->belongsTo(RepairQuote::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getPaymentTypeLabelAttribute(): string
    {
        return self::TYPES[$this->payment_type] ?? $this->payment_type;
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::METHODS[$this->payment_method] ?? $this->payment_method;
    }
}
