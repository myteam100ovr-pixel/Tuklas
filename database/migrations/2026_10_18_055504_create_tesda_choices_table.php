<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tesda_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('qualification_id', 30);
            $table->string('qualification_title');
            $table->string('nc_level', 20);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tesda_choices');
    }
};
