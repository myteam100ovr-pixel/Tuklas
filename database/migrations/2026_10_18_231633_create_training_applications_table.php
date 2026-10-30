<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending');
            $table->timestamps();
            $table->unique(['training_program_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_applications');
    }
};
