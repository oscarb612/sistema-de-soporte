<?php
namespace App\Http\Controllers;
use App\Models\Company;
use App\Services\GroqVisionService;
use Illuminate\Http\Request;
use Throwable;
class StorageLabelAnalysisController extends Controller
{
    public function __invoke(Request $request, GroqVisionService $groq)
    {
        $data=$request->validate(['company_id'=>['required','exists:companies,id'],'image'=>['required','image','mimes:jpeg,jpg,png,webp','max:10240']]);
        $companyId=$request->user()->role==='super_admin'?(int)$data['company_id']:$request->user()->company_id;
        try{return response()->json(['success'=>true,'data'=>$groq->analyzeStorageLabel(Company::findOrFail($companyId),$data['image'])]);}
        catch(Throwable $error){report($error);return response()->json(['success'=>false,'message'=>$error->getMessage()],422);}
    }
}
