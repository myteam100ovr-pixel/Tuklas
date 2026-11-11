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
        Schema::table('employers', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });

        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });

        Schema::table('training_programs', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });

        Schema::table('training_centers', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });

        Schema::table('scholarships', function (Blueprint $table): void {
            $table->boolean('is_demo')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });

        Schema::table('training_programs', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });

        Schema::table('training_centers', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });

        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });

        Schema::table('employers', function (Blueprint $table): void {
            $table->dropColumn('is_demo');
        });
    }
};
