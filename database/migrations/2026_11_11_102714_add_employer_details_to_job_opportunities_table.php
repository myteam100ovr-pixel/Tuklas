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
        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->string('employer_name')->nullable();
            $table->boolean('employer_verified')->default(false);
        });

        DB::table('job_opportunities')->orderBy('id')->get(['id', 'employer_id'])->each(function (object $job): void {
            $employer = DB::table('employers')->where('id', $job->employer_id)->first(['name', 'is_verified']);
            if ($employer !== null) {
                DB::table('job_opportunities')->where('id', $job->id)->update([
                    'employer_name' => $employer->name,
                    'employer_verified' => (bool) $employer->is_verified,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_opportunities', function (Blueprint $table): void {
            $table->dropColumn(['employer_name', 'employer_verified']);
        });
    }
};
