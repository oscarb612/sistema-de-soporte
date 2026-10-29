<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class HomeServicePreorder extends Model {
 public const STATUSES=['prediagnostico'=>'Prediagnóstico','aceptada'=>'Aceptada','agendada'=>'Retiro agendado','en_ruta'=>'Técnico en ruta','en_domicilio'=>'En domicilio','finalizada'=>'Finalizada','cancelada'=>'Cancelada','cliente_ausente'=>'Cliente ausente','otro'=>'Otro'];
 protected $fillable=['company_id','client_id','assigned_user_id','repair_order_id','data_recovery_case_id','code','external_quote_code','service_area','status','address','latitude','longitude','distance_km','pickup_amount','delivery_amount','transport_total','external_snapshot','conversation_summary','prediagnosis','reported_problem','scheduled_at','time_window','unit_type','brand','model','capacity','serial_number','interface','accessories','physical_condition','outcome_details','signer_name','signer_rut','signature_path','signed_at','completed_at'];
 protected $casts=['external_snapshot'=>'array','scheduled_at'=>'datetime','signed_at'=>'datetime','completed_at'=>'datetime','pickup_amount'=>'decimal:2','delivery_amount'=>'decimal:2','transport_total'=>'decimal:2'];
 public function company():BelongsTo{return $this->belongsTo(Company::class);} public function client():BelongsTo{return $this->belongsTo(Client::class);} public function assignedUser():BelongsTo{return $this->belongsTo(User::class,'assigned_user_id');} public function repairOrder():BelongsTo{return $this->belongsTo(RepairOrder::class);} public function dataRecoveryCase():BelongsTo{return $this->belongsTo(DataRecoveryCase::class);} public function photos():HasMany{return $this->hasMany(HomeServicePreorderPhoto::class);} public function getStatusLabelAttribute():string{return self::STATUSES[$this->status]??$this->status;} public function getServiceAreaLabelAttribute():string{return $this->service_area==='recuperadata'?'Recuperadata':'Servicio Técnico';}
}
