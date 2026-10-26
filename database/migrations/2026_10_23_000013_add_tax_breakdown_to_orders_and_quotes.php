<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_orders', function (Blueprint $table) {
            $table->decimal('net_amount', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('net_amount');
        });
        Schema::table('repair_quotes', function (Blueprint $table) {
            $table->decimal('net_amount', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('net_amount');
        });

        DB::table('services')->orderBy('id')->each(function ($service) {
            DB::table('services')->where('id', $service->id)->update(['price' => round((float) $service->price / 1.19, 2)]);
        });

        DB::table('repair_orders')->orderBy('id')->each(function ($order) {
            $net = round((float) $order->total_amount / 1.19, 2);
            DB::table('repair_orders')->where('id', $order->id)->update([
                'labor_amount' => round((float) $order->labor_amount / 1.19, 2),
                'parts_amount' => round((float) $order->parts_amount / 1.19, 2),
                'discount_amount' => round((float) $order->discount_amount / 1.19, 2),
                'net_amount' => $net,
                'tax_amount' => (float) $order->total_amount - $net,
            ]);
        });

        DB::table('repair_order_items')->orderBy('id')->each(function ($item) {
            DB::table('repair_order_items')->where('id', $item->id)->update([
                'unit_price' => round((float) $item->unit_price / 1.19, 2),
                'total' => round((float) $item->total / 1.19, 2),
            ]);
        });

        DB::table('repair_quotes')->orderBy('id')->each(function ($quote) {
            $net = round((float) $quote->total_amount / 1.19, 2);
            DB::table('repair_quotes')->where('id', $quote->id)->update([
                'labor_amount' => round((float) $quote->labor_amount / 1.19, 2),
                'parts_amount' => round((float) $quote->parts_amount / 1.19, 2),
                'discount_amount' => round((float) $quote->discount_amount / 1.19, 2),
                'net_amount' => $net,
                'tax_amount' => (float) $quote->total_amount - $net,
            ]);
        });

        DB::table('repair_quote_items')->orderBy('id')->each(function ($item) {
            DB::table('repair_quote_items')->where('id', $item->id)->update([
                'unit_price' => round((float) $item->unit_price / 1.19, 2),
                'total' => round((float) $item->total / 1.19, 2),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('repair_quotes', fn (Blueprint $table) => $table->dropColumn(['net_amount', 'tax_amount']));
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn(['net_amount', 'tax_amount']));
    }
};
