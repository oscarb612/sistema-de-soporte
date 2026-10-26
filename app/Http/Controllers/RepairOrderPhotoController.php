<?php

namespace App\Http\Controllers;

use App\Models\RepairOrderPhoto;
use Illuminate\Support\Facades\Storage;

class RepairOrderPhotoController extends Controller
{
    public function show(RepairOrderPhoto $photo)
    {
        $user = request()->user();
        abort_if($user->role !== 'super_admin' && $photo->repairOrder->company_id !== $user->company_id, 403);
        abort_unless(Storage::disk('local')->exists($photo->path), 404);
        return Storage::disk('local')->response($photo->path, $photo->original_name.'.webp', ['Content-Type' => 'image/webp']);
    }
}
