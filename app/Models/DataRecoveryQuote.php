<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class DataRecoveryQuote extends Model {
    public const STATUSES=['emitido'=>'Emitido','aprobado'=>'Aprobado','rechazado'=>'Rechazado','vencido'=>'Vencido'];
    protected $fillable=['company_id','data_recovery_case_id','quote_number','status','diagnosis_summary','discount_amount','net_amount','tax_amount','total_amount','valid_until','approved_at','rejected_at','customer_response_notes','internal_notes','quote_type','parent_quote_id'];
    protected $casts=['discount_amount'=>'decimal:2','net_amount'=>'decimal:2','tax_amount'=>'decimal:2','total_amount'=>'decimal:2','valid_until'=>'date','approved_at'=>'datetime','rejected_at'=>'datetime'];
    public function company():BelongsTo{return $this->belongsTo(Company::class);} public function dataRecoveryCase():BelongsTo{return $this->belongsTo(DataRecoveryCase::class);} public function items():HasMany{return $this->hasMany(DataRecoveryQuoteItem::class);} public function payments():HasMany{return $this->hasMany(DataRecoveryPayment::class);} public function getStatusLabelAttribute():string{return self::STATUSES[$this->status]??$this->status;}
}
