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
        Schema::create('career_paths', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('education_level', 60)->nullable();
            $table->string('source_note')->nullable();
            $table->string('status', 12)->default('draft')->index();
            $table->timestamps();
        });

        Schema::create('training_programs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('nc_level', 20)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_hours')->nullable();
            $table->text('requirements')->nullable();
            $table->text('schedule_note')->nullable();
            $table->string('status', 12)->default('draft')->index();
            $table->string('source_reference')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('training_programs');
        Schema::dropIfExists('career_paths');
    }
};
