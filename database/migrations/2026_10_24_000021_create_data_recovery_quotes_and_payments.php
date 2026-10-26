<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('data_recovery_quotes', function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('data_recovery_case_id')->constrained()->cascadeOnDelete();$t->string('quote_number');$t->string('status')->default('emitido');$t->text('diagnosis_summary');$t->decimal('discount_amount',12,2)->default(0);$t->decimal('net_amount',12,2)->default(0);$t->decimal('tax_amount',12,2)->default(0);$t->decimal('total_amount',12,2)->default(0);$t->date('valid_until')->nullable();$t->timestamp('approved_at')->nullable();$t->timestamp('rejected_at')->nullable();$t->text('customer_response_notes')->nullable();$t->text('internal_notes')->nullable();$t->timestamps();$t->unique(['company_id','quote_number']);});
        Schema::create('data_recovery_quote_items', function(Blueprint $t){$t->id();$t->foreignId('data_recovery_quote_id')->constrained()->cascadeOnDelete();$t->string('type');$t->string('description');$t->unsignedInteger('quantity')->default(1);$t->decimal('unit_price',12,2)->default(0);$t->decimal('total',12,2)->default(0);$t->timestamps();});
        Schema::create('data_recovery_payments', function(Blueprint $t){$t->id();$t->foreignId('company_id')->constrained()->cascadeOnDelete();$t->foreignId('data_recovery_case_id')->constrained()->cascadeOnDelete();$t->foreignId('data_recovery_quote_id')->nullable()->constrained()->nullOnDelete();$t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();$t->string('payment_type')->default('abono');$t->string('payment_method')->default('transferencia');$t->decimal('amount',12,2);$t->date('payment_date');$t->text('notes')->nullable();$t->timestamps();$t->index(['data_recovery_case_id','payment_date']);});
    }
    public function down(): void {Schema::dropIfExists('data_recovery_payments');Schema::dropIfExists('data_recovery_quote_items');Schema::dropIfExists('data_recovery_quotes');}
};
