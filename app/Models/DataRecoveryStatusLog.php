<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataRecoveryStatusLog extends Model
{
    protected $fillable = ['data_recovery_case_id', 'user_id', 'status', 'comment'];
    public function dataRecoveryCase(): BelongsTo { return $this->belongsTo(DataRecoveryCase::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
