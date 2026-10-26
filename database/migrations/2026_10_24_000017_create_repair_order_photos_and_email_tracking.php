<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('category')->default('recepcion');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::table('repair_orders', function (Blueprint $table) {
            $table->string('email_status')->nullable()->after('customer_accepted_at');
            $table->timestamp('emailed_at')->nullable()->after('email_status');
            $table->text('email_error')->nullable()->after('emailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn(['email_status', 'emailed_at', 'email_error']));
        Schema::dropIfExists('repair_order_photos');
    }
};
