<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 32)->default('youth')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::table('users')->where('role', 'center_representative')->exists()) {
            throw new RuntimeException('Cannot shrink users.role while Center Representative accounts exist.');
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('role', 20)->default('youth')->change();
        });
    }
};
