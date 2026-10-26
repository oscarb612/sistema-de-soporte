<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class DataRecoveryPhoto extends Model
{
    protected $fillable = ['data_recovery_case_id', 'path', 'original_name', 'width', 'height', 'size'];
    public function dataRecoveryCase(): BelongsTo { return $this->belongsTo(DataRecoveryCase::class); }
}
