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
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->text('requirements')->nullable();
            $table->string('assessment_type')->nullable();
            $table->text('assessment_details')->nullable();
            $table->string('source_url')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->dropColumn(['requirements', 'assessment_type', 'assessment_details', 'source_url']);
        });
    }
};
