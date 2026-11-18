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
            $table->foreignId('training_program_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('slot_limit')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarships', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('training_program_id');
            $table->dropColumn('slot_limit');
        });
    }
};
