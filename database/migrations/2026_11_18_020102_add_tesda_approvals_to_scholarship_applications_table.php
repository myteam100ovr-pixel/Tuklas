<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->string('center_approval_status', 16)->default('not_recorded');
            $table->foreignId('center_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('center_approved_at')->nullable();
            $table->text('center_approval_notes')->nullable();
            $table->string('trainer_approval_status', 16)->default('not_recorded');
            $table->foreignId('trainer_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('trainer_approved_at')->nullable();
            $table->text('trainer_approval_notes')->nullable();
            $table->timestamp('cancelled_at')->nullable();
        });

        DB::table('scholarship_applications')
            ->whereIn('status', ['submitted', 'reviewing'])
            ->update([
                'center_approval_status' => 'pending',
                'trainer_approval_status' => 'pending',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('center_approved_by');
            $table->dropConstrainedForeignId('trainer_approved_by');
            $table->dropColumn([
                'center_approval_status',
                'center_approved_at',
                'center_approval_notes',
                'trainer_approval_status',
                'trainer_approved_at',
                'trainer_approval_notes',
                'cancelled_at',
            ]);
        });
    }
};
