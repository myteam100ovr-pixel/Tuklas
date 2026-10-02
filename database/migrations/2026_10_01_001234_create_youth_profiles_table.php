<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('youth_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('barangay', 100)->nullable();
            $table->string('contact_number', 30)->nullable();
            $table->string('educational_attainment', 60)->nullable();
            $table->string('employment_status', 40)->nullable();
            $table->text('livelihood_interests')->nullable();
            $table->json('interests')->nullable();
            $table->json('skills')->nullable();
            $table->json('credentials')->nullable();
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_relationship', 60)->nullable();
            $table->string('guardian_contact', 30)->nullable();
            $table->timestamp('scan_consent_at')->nullable();
            $table->string('scan_consent_by', 10)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('youth_profiles');
    }
};
