<?php
namespace App\Http\Controllers;
use App\Models\DataRecoveryPhoto;
use Illuminate\Support\Facades\Storage;
class DataRecoveryPhotoController extends Controller
{
    public function show(DataRecoveryPhoto $photo) { abort_if(request()->user()->role !== 'super_admin' && $photo->dataRecoveryCase->company_id !== request()->user()->company_id, 403); return Storage::disk('local')->response($photo->path, $photo->original_name, ['Content-Type' => 'image/webp']); }
    public function destroy(DataRecoveryPhoto $photo) { abort_if(request()->user()->role !== 'super_admin' && $photo->dataRecoveryCase->company_id !== request()->user()->company_id, 403); $case = $photo->dataRecoveryCase; Storage::disk('local')->delete($photo->path); $photo->delete(); return redirect()->route('data-recovery.show', $case)->with('success', 'Fotografía eliminada.'); }
}
