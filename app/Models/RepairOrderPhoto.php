<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RepairOrderPhoto extends Model
{
    protected $fillable = ['repair_order_id', 'device_id', 'path', 'original_name', 'category', 'width', 'height', 'size'];
    public function repairOrder(): BelongsTo { return $this->belongsTo(RepairOrder::class); }
    public function device(): BelongsTo { return $this->belongsTo(Device::class); }
}
