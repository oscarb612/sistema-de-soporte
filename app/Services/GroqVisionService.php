<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqVisionService
{
    public function analyzeStorageLabel(Company $company, UploadedFile $image): array
    {
        if (! $company->groq_api_key) throw new RuntimeException('La empresa no tiene una clave de Groq configurada.');
        $mime = $image->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $schema = ['type'=>'object','properties'=>[
            'brand'=>['type'=>['string','null']], 'model'=>['type'=>['string','null']],
            'capacity'=>['type'=>['string','null']], 'serial_number'=>['type'=>['string','null']],
            'interface'=>['type'=>['string','null']], 'confidence'=>['type'=>'number'],
        ],'required'=>['brand','model','capacity','serial_number','interface','confidence'],'additionalProperties'=>false];
        $response = Http::withToken($company->groq_api_key)->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model'=>$company->groq_model ?: 'qwen/qwen3.8-27b',
            'messages'=>[['role'=>'user','content'=>[
                ['type'=>'text','text'=>'Analiza la etiqueta de esta unidad de almacenamiento. Extrae marca, modelo, capacidad, número de serie e interfaz (por ejemplo SATA, SAS, USB, IDE, NVMe o M.2). No inventes datos ni confundas P/N, firmware, WWN, voltaje o código de barras con el número de serie. Normaliza la capacidad como 500 GB, 1 TB, etc. Usa null cuando un dato no sea visible o no pueda inferirse con alta confianza.'],
                ['type'=>'image_url','image_url'=>['url'=>"data:{$mime};base64,{$base64}"]],
            ]]],
            'response_format'=>['type'=>'json_schema','json_schema'=>['name'=>'storage_label','strict'=>true,'schema'=>$schema]],
            'reasoning_effort'=>'none','temperature'=>0,
        ]);
        if ($response->failed()) throw new RuntimeException('Groq no pudo analizar la imagen: '.$response->json('error.message','error de conexión'));
        $result=json_decode((string)$response->json('choices.0.message.content'),true);
        if (!is_array($result)) throw new RuntimeException('Groq devolvió una respuesta no válida.');
        return $result;
    }

    public function analyzeDeviceLabel(Company $company, UploadedFile $image): array
    {
        if (! $company->groq_api_key) throw new RuntimeException('La empresa no tiene una clave de Groq configurada.');
        $mime = $image->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode(file_get_contents($image->getRealPath()));
        $schema = [
            'type' => 'object',
            'properties' => [
                'brand' => ['type' => ['string', 'null']],
                'model' => ['type' => ['string', 'null']],
                'serial_number' => ['type' => ['string', 'null']],
                'confidence' => ['type' => 'number'],
            ],
            'required' => ['brand', 'model', 'serial_number', 'confidence'],
            'additionalProperties' => false,
        ];

        $response = Http::withToken($company->groq_api_key)->timeout(45)->post('https://api.groq.com/openai/v1/chat/completions', [
            'model' => $company->groq_model ?: 'qwen/qwen3.8-27b',
            'messages' => [[
                'role' => 'user',
                'content' => [
                    ['type' => 'text', 'text' => 'Lee la etiqueta posterior de este notebook. Extrae únicamente marca, modelo y número de serie. No confundas número de producto, SKU o voltaje con el número de serie. Usa null si un dato no es legible.'],
                    ['type' => 'image_url', 'image_url' => ['url' => "data:{$mime};base64,{$base64}"]],
                ],
            ]],
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'device_label', 'strict' => true, 'schema' => $schema]],
            'reasoning_effort' => 'none',
            'temperature' => 0,
        ]);

        if ($response->failed()) throw new RuntimeException('Groq no pudo analizar la imagen: '.$response->json('error.message', 'error de conexión'));
        $result = json_decode((string) $response->json('choices.0.message.content'), true);
        if (! is_array($result)) throw new RuntimeException('Groq devolvió una respuesta no válida.');
        return $result;
    }
}
