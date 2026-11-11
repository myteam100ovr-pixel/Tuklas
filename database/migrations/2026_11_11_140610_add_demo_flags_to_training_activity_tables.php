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
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });
        Schema::table('training_applications', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });
        Schema::table('assessment_records', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_records', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
        Schema::table('training_applications', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
    }
};
