<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataRecoveryPayment extends Model {
    public const TYPES=['abono'=>'Abono','pago_final'=>'Pago final','otro'=>'Otro']; public const METHODS=['transferencia'=>'Transferencia','efectivo'=>'Efectivo','tarjeta'=>'Tarjeta','otro'=>'Otro'];
    protected $fillable=['company_id','data_recovery_case_id','data_recovery_quote_id','created_by','payment_type','payment_method','amount','payment_date','notes'];protected $casts=['amount'=>'decimal:2','payment_date'=>'date'];
    public function dataRecoveryCase():BelongsTo{return $this->belongsTo(DataRecoveryCase::class);}public function dataRecoveryQuote():BelongsTo{return $this->belongsTo(DataRecoveryQuote::class);}public function creator():BelongsTo{return $this->belongsTo(User::class,'created_by');}public function getPaymentTypeLabelAttribute():string{return self::TYPES[$this->payment_type]??$this->payment_type;}public function getPaymentMethodLabelAttribute():string{return self::METHODS[$this->payment_method]??$this->payment_method;}
}
