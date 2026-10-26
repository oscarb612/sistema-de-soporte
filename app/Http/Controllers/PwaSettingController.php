<?php
namespace App\Http\Controllers;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Http\Request;
class PwaSettingController extends Controller {
 public function show(Request $request){$url=route('home-service.mobile-app');return view('pwa-settings.show',['pwaUrl'=>$url,'isHttps'=>str_starts_with($url,'https://'),'isLocal'=>in_array(parse_url($url,PHP_URL_HOST),['localhost','127.0.0.1'],true)]);}
 public function qr(Request $request){$url=route('home-service.mobile-app');$result=(new Builder(writer:new SvgWriter(),writerOptions:[],validateResult:false,data:$url,encoding:new Encoding('UTF-8'),errorCorrectionLevel:ErrorCorrectionLevel::High,size:420,margin:18,roundBlockSizeMode:RoundBlockSizeMode::Margin))->build();$headers=['Content-Type'=>$result->getMimeType(),'Cache-Control'=>'private, no-store, max-age=0'];if($request->boolean('download'))$headers['Content-Disposition']='attachment; filename="conexionweb-retiros-qr.svg"';return response($result->getString(),200,$headers);}
}
