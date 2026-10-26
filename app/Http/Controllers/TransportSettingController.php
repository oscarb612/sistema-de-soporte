<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class TransportSettingController extends Controller {
 public function edit(Request $r){$companies=$this->companies($r);$id=$r->user()->role==='super_admin'?$r->integer('company_id'):$r->user()->company_id;$company=$companies->firstWhere('id',$id)??$companies->first();return view('transport-settings.edit',compact('companies','company'));}
 public function update(Request $r){$id=$r->user()->role==='super_admin'?$r->integer('company_id'):$r->user()->company_id;$data=$r->validate(['company_id'=>['nullable',Rule::exists('companies','id')],'transport_api_url'=>['required','url','max:255'],'transport_api_key'=>['nullable','string','min:32','max:255']]);$company=Company::findOrFail($id);abort_if($r->user()->role!=='super_admin'&&$company->id!==$r->user()->company_id,403);$values=['transport_api_url'=>rtrim($data['transport_api_url'],'/')];if(!empty($data['transport_api_key']))$values['transport_api_key']=$data['transport_api_key'];$company->update($values);ActivityLog::record($r,'transport_settings_updated','Integración de retiros actualizada para '.$company->name,$company,$company->id);return redirect()->route('transport-settings.edit',['company_id'=>$company->id])->with('success','Integración de retiros guardada correctamente.');}
 private function companies(Request $r){return Company::query()->when($r->user()->role!=='super_admin',fn($q)=>$q->whereKey($r->user()->company_id))->orderBy('name')->get();}
}
