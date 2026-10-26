<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void { Schema::table('data_recovery_cases', function (Blueprint $table) { $table->string('email_status')->default('pendiente'); $table->text('email_error')->nullable(); $table->timestamp('emailed_at')->nullable(); }); }
    public function down(): void { Schema::table('data_recovery_cases', fn (Blueprint $table) => $table->dropColumn(['email_status','email_error','emailed_at'])); }
};
