<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('groq_api_key')->nullable()->after('mail_from_address');
            $table->string('groq_model')->default('qwen/qwen3.8-27b')->after('groq_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['groq_api_key', 'groq_model']));
    }
};
