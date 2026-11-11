<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->string('compensation')->nullable()->after('title');
            $table->string('source_url')->nullable()->after('application_url');
            $table->timestamp('source_checked_at')->nullable()->after('source_url');
            $table->unique('source_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->dropUnique(['source_url']);
            $table->dropColumn(['compensation', 'source_url', 'source_checked_at']);
        });
    }
};
