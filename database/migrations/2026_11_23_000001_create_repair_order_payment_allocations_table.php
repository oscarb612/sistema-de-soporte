<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_order_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_quote_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();
            $table->unique(['repair_order_payment_id', 'repair_quote_id'], 'payment_quote_allocation_unique');
        });

        DB::table('repair_order_payments')->whereNotNull('repair_quote_id')->orderBy('id')->each(function ($payment) {
            DB::table('repair_order_payment_allocations')->insert([
                'repair_order_payment_id' => $payment->id, 'repair_quote_id' => $payment->repair_quote_id,
                'amount' => $payment->amount, 'created_at' => $payment->created_at, 'updated_at' => $payment->updated_at,
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_order_payment_allocations');
    }
};
