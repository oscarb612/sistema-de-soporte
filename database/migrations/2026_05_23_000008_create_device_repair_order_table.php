<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_repair_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['repair_order_id', 'device_id']);
        });

        DB::table('repair_orders')
            ->whereNotNull('device_id')
            ->orderBy('id')
            ->get(['id', 'device_id'])
            ->each(function ($order): void {
                DB::table('device_repair_order')->insert([
                    'repair_order_id' => $order->id,
                    'device_id' => $order->device_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_repair_order');
    }
};
