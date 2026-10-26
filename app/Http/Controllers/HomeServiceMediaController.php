<?php
namespace App\Http\Controllers;
use App\Models\{HomeServicePreorder,HomeServicePreorderPhoto};
use Illuminate\Support\Facades\Storage;
class HomeServiceMediaController extends Controller {
 public function photo(HomeServicePreorderPhoto $photo){$this->authorize($photo->preorder);return Storage::disk('local')->response($photo->path,$photo->original_name,['Content-Type'=>'image/webp']);}
 public function signature(HomeServicePreorder $homeService){$this->authorize($homeService);abort_unless($homeService->signature_path&&Storage::disk('local')->exists($homeService->signature_path),404);return Storage::disk('local')->response($homeService->signature_path,'firma-'.$homeService->code.'.webp',['Content-Type'=>'image/webp']);}
 private function authorize(HomeServicePreorder $p):void{abort_if(request()->user()->role!=='super_admin'&&$p->company_id!==request()->user()->company_id,403);}
}
