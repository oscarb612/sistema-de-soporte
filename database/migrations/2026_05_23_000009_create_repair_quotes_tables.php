<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_order_id')->constrained()->cascadeOnDelete();
            $table->string('quote_number');
            $table->string('status')->default('emitido');
            $table->text('diagnosis_summary');
            $table->decimal('labor_amount', 12, 2)->default(0);
            $table->decimal('parts_amount', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('customer_response_notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'quote_number']);
        });

        Schema::create('repair_quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_quote_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('description');
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_quote_items');
        Schema::dropIfExists('repair_quotes');
    }
};
