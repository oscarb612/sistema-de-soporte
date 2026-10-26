<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_recovery_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code');
            $table->string('status')->default('recibido');
            $table->string('unit_type');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('capacity')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('interface')->nullable();
            $table->string('failure_reason')->nullable();
            $table->boolean('previously_opened')->default(false);
            $table->boolean('contains_sensitive_data')->default(false);
            $table->text('priority_data')->nullable();
            $table->text('access_credentials')->nullable();
            $table->text('accessories')->nullable();
            $table->text('observations')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('result_summary')->nullable();
            $table->decimal('net_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'status']);
        });

        Schema::create('data_recovery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_recovery_case_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->timestamps();
        });

        Schema::create('data_recovery_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_recovery_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_recovery_status_logs');
        Schema::dropIfExists('data_recovery_photos');
        Schema::dropIfExists('data_recovery_cases');
    }
};
