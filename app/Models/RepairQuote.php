<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairQuote extends Model
{
    use HasFactory;

    public const STATUSES = [
        'emitido' => 'Emitido',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'vencido' => 'Vencido',
    ];

    protected $fillable = [
        'company_id',
        'repair_order_id',
        'quote_number',
        'status',
        'diagnosis_summary',
        'labor_amount',
        'parts_amount',
        'discount_amount',
        'net_amount',
        'tax_amount',
        'total_amount',
        'valid_until',
        'approved_at',
        'rejected_at',
        'customer_response_notes',
        'internal_notes',
        'quote_type',
        'parent_quote_id',
    ];

    protected $casts = [
        'labor_amount' => 'decimal:2',
        'parts_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function repairOrder(): BelongsTo
    {
        return $this->belongsTo(RepairOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RepairQuoteItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RepairOrderPayment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
