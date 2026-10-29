<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_service_preorders', function (Blueprint $table) {
            $table->text('outcome_details')->nullable()->after('physical_condition');
        });
    }

    public function down(): void
    {
        Schema::table('home_service_preorders', fn (Blueprint $table) => $table->dropColumn('outcome_details'));
    }
};
