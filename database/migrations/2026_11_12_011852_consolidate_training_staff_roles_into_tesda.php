<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereIn('role', ['trainer', 'center_representative'])
            ->update(['role' => 'tesda_admin']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Role consolidation cannot be reversed without losing each account's original role.
    }
};
