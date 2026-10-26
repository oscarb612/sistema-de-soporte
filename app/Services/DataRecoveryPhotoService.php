<?php

namespace App\Services;

use App\Models\DataRecoveryCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DataRecoveryPhotoService
{
    public function store(DataRecoveryCase $case, UploadedFile $file)
    {
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()) ?: '');
        if (! $source) throw new RuntimeException('No fue posible procesar una de las fotografías.');
        $width = imagesx($source); $height = imagesy($source);
        $scale = min(1, 1600 / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        ob_start(); imagewebp($target, null, 78); $webp = ob_get_clean();
        imagedestroy($source); imagedestroy($target);
        if (! $webp) throw new RuntimeException('No fue posible convertir la fotografía a WebP.');
        $path = 'data-recovery/'.$case->id.'/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $webp);
        return $case->photos()->create(['path' => $path, 'original_name' => $file->getClientOriginalName(), 'width' => $newWidth, 'height' => $newHeight, 'size' => strlen($webp)]);
    }
}
