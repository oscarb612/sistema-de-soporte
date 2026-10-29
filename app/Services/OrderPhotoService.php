<?php

namespace App\Services;

use App\Models\RepairOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class OrderPhotoService
{
    public function store(RepairOrder $order, UploadedFile $file, bool $isLabel = false)
    {
        return $this->storeWithCategory($order, $file, $isLabel ? 'etiqueta' : 'recepcion');
    }

    public function storeDiagnosis(RepairOrder $order, UploadedFile $file)
    {
        return $this->storeWithCategory($order, $file, 'diagnostico');
    }

    private function storeWithCategory(RepairOrder $order, UploadedFile $file, string $category)
    {
        $contents = file_get_contents($file->getRealPath());
        $source = @imagecreatefromstring($contents ?: '');
        if (! $source) throw new RuntimeException('No fue posible procesar una de las fotografías.');

        $width = imagesx($source); $height = imagesy($source);
        $scale = min(1, 1600 / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));
        $target = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($target, false); imagesavealpha($target, true);
        $transparent = imagecolorallocatealpha($target, 0, 0, 0, 127);
        imagefilledrectangle($target, 0, 0, $newWidth, $newHeight, $transparent);
        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start(); imagewebp($target, null, 78); $webp = ob_get_clean();
        imagedestroy($source); imagedestroy($target);
        if (! $webp) throw new RuntimeException('No fue posible convertir la fotografía a WebP.');

        $path = 'repair-orders/'.$order->id.'/'.Str::uuid().'.webp';
        Storage::disk('local')->put($path, $webp);

        return $order->photos()->create([
            'device_id' => $order->device_id,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'category' => $category,
            'width' => $newWidth,
            'height' => $newHeight,
            'size' => strlen($webp),
        ]);
    }
}
