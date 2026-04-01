<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_hunter_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('barangay', 120)->index();
            $table->string('full_name', 180);
            $table->string('desired_job', 180);
            $table->string('skills', 1500);
            $table->string('preferred_setup', 40);
            $table->string('expected_salary', 120);
            $table->string('barangay_zone', 120);
            $table->boolean('available_now')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_hunter_profiles');
    }
};
