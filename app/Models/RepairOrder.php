<?php

namespace App\Models;

use App\Services\BusinessDeadlineService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RepairOrder extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (RepairOrder $order) {
            if ($order->service_mode === 'diagnostico' && ! $order->diagnosis_due_at) {
                $hours = Company::find($order->company_id)?->technical_diagnostic_hours ?? 24;
                $order->diagnosis_due_at = app(BusinessDeadlineService::class)->addBusinessHours(now(), $hours);
            }
        });
    }

    public const STATUSES = [
        'ingresado' => 'Ingresado',
        'en_revision' => 'En revisión',
        'diagnosticado' => 'Diagnosticado',
        'presupuesto_emitido' => 'Presupuesto emitido',
        'esperando_aprobacion' => 'Esperando aprobación',
        'aprobado_por_cliente' => 'Aprobado por cliente',
        'rechazado_por_cliente' => 'Rechazado por cliente',
        'esperando_repuesto' => 'Esperando repuesto',
        'en_reparacion' => 'En reparación',
        'servicio_aceptado' => 'Servicio aceptado',
        'en_ejecucion' => 'En ejecución',
        'terminado' => 'Terminado',
        'reparado' => 'Reparado',
        'finalizado' => 'Finalizado',
        'entregado' => 'Entregado',
        'no_reparado' => 'No reparado',
        'cancelado' => 'Cancelado',
    ];

    public const TERMINAL_STATUSES = [
        'finalizado',
        'entregado',
        'cancelado',
        'rechazado_por_cliente',
    ];

    protected $fillable = [
        'company_id',
        'client_id',
        'device_id',
        'code',
        'status',
        'service_mode',
        'reported_problem',
        'initial_observations',
        'technical_diagnosis',
        'diagnosis_due_at',
        'labor_amount',
        'parts_amount',
        'discount_amount',
        'net_amount',
        'tax_amount',
        'total_amount',
        'estimated_delivery_date',
        'customer_accepted_at',
        'email_status',
        'emailed_at',
        'email_error',
        'delivered_at',
    ];

    protected $casts = [
        'estimated_delivery_date' => 'date',
        'delivered_at' => 'datetime',
        'customer_accepted_at' => 'datetime',
        'emailed_at' => 'datetime',
        'diagnosis_due_at' => 'datetime',
        'labor_amount' => 'decimal:2',
        'parts_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function devices(): BelongsToMany
    {
        return $this->belongsToMany(Device::class)->withTimestamps();
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(RepairOrderStatusLog::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RepairOrderItem::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(RepairQuote::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RepairOrderPayment::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(RepairOrderPhoto::class);
    }

    public function homeServicePreorder(): HasOne
    {
        return $this->hasOne(HomeServicePreorder::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::TERMINAL_STATUSES);
    }

    public function getStatusLabelAttribute(): string
    {
        if (! in_array($this->status, self::TERMINAL_STATUSES, true)) {
            $activeQuote = $this->activePayableQuote();
            $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();
            $installmentCount = $payments
                ->where('payment_type', 'abono')
                ->when($activeQuote, fn ($items) => $items->where('repair_quote_id', $activeQuote->id))
                ->count();

            if ($installmentCount > 0) {
                return $this->installmentOrdinal($installmentCount).' abono';
            }
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }

    private function installmentOrdinal(int $number): string
    {
        return match ($number) {
            1 => '1er',
            2 => '2do',
            3 => '3er',
            4 => '4to',
            5 => '5to',
            6 => '6to',
            7 => '7mo',
            8 => '8vo',
            9 => '9no',
            10 => '10mo',
            default => $number.'°',
        };
    }

    public function getServiceModeLabelAttribute(): string
    {
        return $this->service_mode === 'precio_definido' ? 'Servicio directo' : 'Con diagnóstico';
    }

    public function activePayableQuote(): ?RepairQuote
    {
        $quotes = $this->relationLoaded('quotes') ? $this->quotes : $this->quotes()->get();

        return $quotes->where('quote_type', '!=', 'closure')->where('status', 'aprobado')->sortByDesc('id')->first()
            ?? $quotes->where('quote_type', '!=', 'closure')->where('status', 'emitido')->sortByDesc('id')->first()
            ?? $quotes->where('quote_type', 'closure')->whereIn('status', ['emitido', 'aprobado'])->sortByDesc('id')->first();
    }

    public function payableTotal(): float
    {
        $activeQuote = $this->activePayableQuote();

        return $activeQuote ? (float) $activeQuote->total_amount : (float) $this->total_amount;
    }

    public function paidTotal(): float
    {
        $activeQuote = $this->activePayableQuote();
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        return (float) ($activeQuote
            ? $payments->where('repair_quote_id', $activeQuote->id)->sum('amount')
            : $payments->sum('amount'));
    }

    public function pendingBalance(): float
    {
        return max($this->payableTotal() - $this->paidTotal(), 0);
    }
}
