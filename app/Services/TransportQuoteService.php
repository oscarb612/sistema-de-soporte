<?php
namespace App\Services;
use App\Models\Company;
use Illuminate\Support\Facades\Http;
use RuntimeException;
class TransportQuoteService {
 public function find(Company $company,string $code):array{$this->configured($company);$url=rtrim($company->transport_api_url,'/').'/wp-json/cre/v1/internal/quotes/'.rawurlencode(strtoupper(trim($code)));$r=Http::acceptJson()->withHeaders(['X-CRE-API-Key'=>$company->transport_api_key])->timeout(20)->get($url);if($r->failed())throw new RuntimeException($r->json('message','No fue posible consultar la estimación de traslado.'));$data=$r->json();if(!is_array($data)||empty($data['code']))throw new RuntimeException('La API de traslado devolvió una respuesta no válida.');return $data;}
 public function claim(Company $company,string $quoteCode,string $preorderCode):array{$this->configured($company);$url=rtrim($company->transport_api_url,'/').'/wp-json/cre/v1/internal/quotes/'.rawurlencode($quoteCode).'/claim';$r=Http::acceptJson()->withHeaders(['X-CRE-API-Key'=>$company->transport_api_key])->timeout(20)->post($url,['preorder_code'=>$preorderCode]);if($r->failed())throw new RuntimeException($r->json('message','No fue posible vincular la estimación con la preorden.'));return $r->json();}
 private function configured(Company $company):void{if(!$company->transport_api_url||!$company->transport_api_key)throw new RuntimeException('Configura la URL de WordPress y la clave privada de integración.');}
}
