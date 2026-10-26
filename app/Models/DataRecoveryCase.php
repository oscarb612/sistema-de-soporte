<?php

namespace App\Models;

use App\Services\BusinessDeadlineService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataRecoveryCase extends Model
{
    protected static function booted(): void
    {
        static::creating(function (DataRecoveryCase $case) {
            if (! $case->diagnosis_due_at) {
                $hours = Company::find($case->company_id)?->recovery_diagnostic_hours ?? 48;
                $case->diagnosis_due_at = app(BusinessDeadlineService::class)->addBusinessHours(now(), $hours);
            }
        });
    }
    public const STATUSES = [
        'recibido' => 'Recibido',
        'evaluacion' => 'Evaluación inicial',
        'diagnosticado' => 'Diagnóstico terminado',
        'presupuesto_enviado' => 'Presupuesto enviado',
        'aprobado' => 'Aprobado',
        'rechazado' => 'Rechazado',
        'en_recuperacion' => 'Recuperación en proceso',
        'finalizado' => 'Recuperación finalizada',
        'entregado' => 'Entregado',
        'no_recuperable' => 'No recuperable',
    ];

    public const UNIT_TYPES = ['HDD 2.5"', 'HDD 3.5"', 'SSD SATA', 'SSD M.2', 'SSD NVMe', 'Pendrive', 'Tarjeta SD', 'Teléfono', 'Otro'];
    public const FAILURE_REASONS = ['Eliminación accidental', 'Formateo', 'Caída o golpe', 'Daño eléctrico', 'Daño por líquido', 'No reconocido', 'Falla mecánica', 'Otro'];

    protected $fillable = ['company_id', 'client_id', 'received_by', 'code', 'status', 'unit_type', 'brand', 'model', 'capacity', 'serial_number', 'interface', 'failure_reason', 'previously_opened', 'contains_sensitive_data', 'priority_data', 'access_credentials', 'accessories', 'observations', 'diagnosis', 'result_summary', 'diagnosis_due_at', 'net_amount', 'tax_amount', 'total_amount', 'received_at', 'delivered_at', 'email_status', 'email_error', 'emailed_at'];
    protected $casts = ['previously_opened' => 'boolean', 'contains_sensitive_data' => 'boolean', 'access_credentials' => 'encrypted', 'received_at' => 'datetime', 'diagnosis_due_at' => 'datetime', 'delivered_at' => 'datetime', 'emailed_at' => 'datetime', 'net_amount' => 'decimal:2', 'tax_amount' => 'decimal:2', 'total_amount' => 'decimal:2'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    public function photos(): HasMany { return $this->hasMany(DataRecoveryPhoto::class); }
    public function statusLogs(): HasMany { return $this->hasMany(DataRecoveryStatusLog::class); }
    public function quotes(): HasMany { return $this->hasMany(DataRecoveryQuote::class); }
    public function payments(): HasMany { return $this->hasMany(DataRecoveryPayment::class); }
    public function homeServicePreorder(): HasOne { return $this->hasOne(HomeServicePreorder::class); }
    public function getStatusLabelAttribute(): string { return self::STATUSES[$this->status] ?? $this->status; }
}
