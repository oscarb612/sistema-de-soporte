<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('diagnostic_fee_gross', 12, 2)->default(20000);
            $table->unsignedSmallInteger('technical_diagnostic_hours')->default(24);
            $table->unsignedSmallInteger('recovery_diagnostic_hours')->default(48);
        });
        Schema::table('repair_orders', fn (Blueprint $table) => $table->timestamp('diagnosis_due_at')->nullable());
        Schema::table('data_recovery_cases', fn (Blueprint $table) => $table->timestamp('diagnosis_due_at')->nullable());
        Schema::table('repair_quotes', function (Blueprint $table) {
            $table->string('quote_type')->default('repair');
            $table->foreignId('parent_quote_id')->nullable()->constrained('repair_quotes')->nullOnDelete();
        });
        Schema::table('data_recovery_quotes', function (Blueprint $table) {
            $table->string('quote_type')->default('recovery');
            $table->foreignId('parent_quote_id')->nullable()->constrained('data_recovery_quotes')->nullOnDelete();
        });
    }

    public function down(): void {
        Schema::table('data_recovery_quotes', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_quote_id'));
        Schema::table('data_recovery_quotes', fn (Blueprint $table) => $table->dropColumn('quote_type'));
        Schema::table('repair_quotes', fn (Blueprint $table) => $table->dropConstrainedForeignId('parent_quote_id'));
        Schema::table('repair_quotes', fn (Blueprint $table) => $table->dropColumn('quote_type'));
        Schema::table('data_recovery_cases', fn (Blueprint $table) => $table->dropColumn('diagnosis_due_at'));
        Schema::table('repair_orders', fn (Blueprint $table) => $table->dropColumn('diagnosis_due_at'));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['diagnostic_fee_gross','technical_diagnostic_hours','recovery_diagnostic_hours']));
    }
};
