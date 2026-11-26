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
            $table->foreignId('trainer_id')->nullable()->after('training_center_id')->constrained('users')->nullOnDelete();
            $table->json('training_dates')->nullable()->after('ends_at');
            $table->boolean('skip_weekends')->default(false)->after('training_dates');
            $table->json('non_working_dates')->nullable()->after('skip_weekends');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table): void {
            $table->dropForeign(['trainer_id']);
            $table->dropColumn(['trainer_id', 'training_dates', 'skip_weekends', 'non_working_dates']);
        });
    }
};
