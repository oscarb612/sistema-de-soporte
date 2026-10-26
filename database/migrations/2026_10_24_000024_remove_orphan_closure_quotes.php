<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        DB::table('repair_quotes')->where('quote_type', 'closure')->whereNull('parent_quote_id')->delete();
        DB::table('data_recovery_quotes')->where('quote_type', 'closure')->whereNull('parent_quote_id')->delete();
    }

    public function down(): void {}
};
