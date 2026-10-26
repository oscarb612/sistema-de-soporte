<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->decimal('estimated_hours', 8, 2)->nullable()->after('price');
        });

        DB::table('services')->whereNotNull('estimated_minutes')->update([
            'estimated_hours' => DB::raw('estimated_minutes / 60.0'),
        ]);

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('estimated_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->unsignedInteger('estimated_minutes')->nullable()->after('price');
        });

        DB::table('services')->whereNotNull('estimated_hours')->update([
            'estimated_minutes' => DB::raw('ROUND(estimated_hours * 60)'),
        ]);

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('estimated_hours');
        });
    }
};
