<?php
namespace App\Services;
use App\Models\HomeServicePreorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
class HomeServicePhotoService {public function store(HomeServicePreorder $preorder,UploadedFile $file,bool $label=false){$source=@imagecreatefromstring(file_get_contents($file->getRealPath())?:'');if(!$source)throw new RuntimeException('No fue posible procesar una fotografía.');$w=imagesx($source);$h=imagesy($source);$scale=min(1,1600/max($w,$h));$nw=max(1,(int)round($w*$scale));$nh=max(1,(int)round($h*$scale));$target=imagecreatetruecolor($nw,$nh);imagecopyresampled($target,$source,0,0,0,0,$nw,$nh,$w,$h);ob_start();imagewebp($target,null,78);$webp=ob_get_clean();imagedestroy($source);imagedestroy($target);if(!$webp)throw new RuntimeException('No fue posible convertir la fotografía.');$path='home-service/'.$preorder->id.'/'.Str::uuid().'.webp';Storage::disk('local')->put($path,$webp);return $preorder->photos()->create(['path'=>$path,'original_name'=>$file->getClientOriginalName(),'category'=>$label?'etiqueta':'recepcion','width'=>$nw,'height'=>$nh,'size'=>strlen($webp)]);}}
