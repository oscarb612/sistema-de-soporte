<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataRecoveryQuoteItem extends Model {protected $fillable=['data_recovery_quote_id','type','description','quantity','unit_price','total'];protected $casts=['unit_price'=>'decimal:2','total'=>'decimal:2'];public function dataRecoveryQuote():BelongsTo{return $this->belongsTo(DataRecoveryQuote::class);}}
