<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairQuoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'repair_quote_id',
        'type',
        'description',
        'quantity',
        'unit_price',
        'total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function repairQuote(): BelongsTo
    {
        return $this->belongsTo(RepairQuote::class);
    }
}
