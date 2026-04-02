<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_hunter_invitations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inviter_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('talent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('talent_profile_id')->nullable()->constrained('job_hunter_profiles')->nullOnDelete();
            $table->string('barangay', 120)->index();
            $table->string('talent_name', 180);
            $table->string('talent_mobile', 40)->nullable()->index();
            $table->string('talent_desired_job', 180)->nullable();
            $table->string('inviter_name', 180);
            $table->string('inviter_mobile', 40)->nullable();
            $table->text('message');
            $table->string('status', 40)->default('Pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_hunter_invitations');
    }
};

