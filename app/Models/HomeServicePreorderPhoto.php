<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class HomeServicePreorderPhoto extends Model {protected $fillable=['home_service_preorder_id','path','original_name','category','width','height','size'];public function preorder():BelongsTo{return $this->belongsTo(HomeServicePreorder::class,'home_service_preorder_id');}}
