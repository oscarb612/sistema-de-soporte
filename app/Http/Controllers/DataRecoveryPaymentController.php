<?php
namespace App\Http\Controllers;
use App\Models\ActivityLog;
use App\Models\DataRecoveryCase;
use App\Models\DataRecoveryPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class DataRecoveryPaymentController extends Controller {
    public function store(Request $request,DataRecoveryCase $dataRecovery){$this->authorizeCase($dataRecovery);$quoteIds=$dataRecovery->quotes()->pluck('id')->all();$data=$request->validate(['data_recovery_quote_id'=>['nullable','integer',Rule::in($quoteIds)],'payment_type'=>['required',Rule::in(array_keys(DataRecoveryPayment::TYPES))],'payment_method'=>['required',Rule::in(array_keys(DataRecoveryPayment::METHODS))],'amount'=>['required','numeric','min:1'],'payment_date'=>['required','date'],'notes'=>['nullable','string']]);$payment=DataRecoveryPayment::create($data+['company_id'=>$dataRecovery->company_id,'data_recovery_case_id'=>$dataRecovery->id,'created_by'=>$request->user()->id]);ActivityLog::record($request,'data_recovery_payment_created','Abono en '.$dataRecovery->code.': $'.number_format((float)$payment->amount,0,',','.'),$dataRecovery,$dataRecovery->company_id);return redirect()->route('data-recovery.show',$dataRecovery)->with('success','Abono registrado correctamente.');}
    public function destroy(Request $request,DataRecoveryCase $dataRecovery,DataRecoveryPayment $payment){$this->authorizeCase($dataRecovery);abort_if($payment->data_recovery_case_id!==$dataRecovery->id,404);$amount=$payment->amount;$payment->delete();ActivityLog::record($request,'data_recovery_payment_deleted','Abono eliminado de '.$dataRecovery->code.': $'.number_format((float)$amount,0,',','.'),$dataRecovery,$dataRecovery->company_id);return redirect()->route('data-recovery.show',$dataRecovery)->with('success','Abono eliminado correctamente.');}
    private function authorizeCase(DataRecoveryCase $case):void{abort_if(request()->user()->role!=='super_admin'&&$case->company_id!==request()->user()->company_id,403);}
}
