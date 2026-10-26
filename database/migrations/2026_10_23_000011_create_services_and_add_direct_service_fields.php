<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->string('service_mode')->default('diagnostico')->after('status');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('parts_amount');
            $table->timestamp('customer_accepted_at')->nullable()->after('estimated_delivery_date');
        });

        Schema::table('repair_order_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('repair_order_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('repair_order_items', fn (Blueprint $table) => $table->dropConstrainedForeignId('service_id'));
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn(['service_mode', 'discount_amount', 'customer_accepted_at']));
        Schema::dropIfExists('services');
    }
};
