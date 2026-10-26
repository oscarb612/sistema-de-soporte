<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'description', 'price', 'estimated_hours', 'is_active'];
    protected $casts = ['price' => 'decimal:2', 'estimated_hours' => 'decimal:2', 'is_active' => 'boolean'];

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function orderItems(): HasMany { return $this->hasMany(RepairOrderItem::class); }
}
