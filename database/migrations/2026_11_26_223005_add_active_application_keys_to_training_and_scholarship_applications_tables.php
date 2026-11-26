<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->dropUnique('scholarship_applications_scholarship_id_user_id_unique');
            $table->char('active_application_key', 64)->nullable()->unique();
        });

        Schema::table('training_applications', function (Blueprint $table): void {
            $table->dropUnique('training_applications_training_program_id_user_id_unique');
            $table->char('active_application_key', 64)->nullable()->unique();
        });

        DB::table('scholarship_applications')
            ->whereNotIn('status', ['cancelled', 'rejected', 'failed'])
            ->orderBy('id')
            ->chunkById(500, function ($applications): void {
                foreach ($applications as $application) {
                    DB::table('scholarship_applications')
                        ->where('id', $application->id)
                        ->update(['active_application_key' => hash('sha256', $application->user_id.':'.$application->scholarship_id)]);
                }
            });

        DB::table('training_applications')
            ->whereIn('status', ['pending', 'accepted'])
            ->orderBy('id')
            ->chunkById(500, function ($applications): void {
                foreach ($applications as $application) {
                    DB::table('training_applications')
                        ->where('id', $application->id)
                        ->update(['active_application_key' => hash('sha256', $application->user_id.':'.$application->training_program_id)]);
                }
            });
    }

    public function down(): void
    {
        $hasScholarshipReapplications = DB::table('scholarship_applications')
            ->select(['scholarship_id', 'user_id'])
            ->groupBy(['scholarship_id', 'user_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();
        $hasCourseReapplications = DB::table('training_applications')
            ->select(['training_program_id', 'user_id'])
            ->groupBy(['training_program_id', 'user_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hasScholarshipReapplications || $hasCourseReapplications) {
            throw new \RuntimeException('Cannot restore the original application uniqueness indexes while reapplication history exists.');
        }

        Schema::table('scholarship_applications', function (Blueprint $table): void {
            $table->dropUnique('scholarship_applications_active_application_key_unique');
            $table->dropColumn('active_application_key');
            $table->unique(['scholarship_id', 'user_id']);
        });

        Schema::table('training_applications', function (Blueprint $table): void {
            $table->dropUnique('training_applications_active_application_key_unique');
            $table->dropColumn('active_application_key');
            $table->unique(['training_program_id', 'user_id']);
        });
    }
};
