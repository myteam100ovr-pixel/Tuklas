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
        Schema::table('job_recommendations', function (Blueprint $table): void {
            $table->timestamp('not_selected_at')->nullable();
            $table->foreignId('not_selected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('not_selected_reason')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_recommendations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('not_selected_by');
            $table->dropColumn(['not_selected_at', 'not_selected_reason']);
        });
    }
};
