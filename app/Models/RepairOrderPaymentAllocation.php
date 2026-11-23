<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairOrderPaymentAllocation extends Model
{
    protected $fillable = ['repair_order_payment_id', 'repair_quote_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function payment(): BelongsTo { return $this->belongsTo(RepairOrderPayment::class, 'repair_order_payment_id'); }
    public function quote(): BelongsTo { return $this->belongsTo(RepairQuote::class, 'repair_quote_id'); }
}
