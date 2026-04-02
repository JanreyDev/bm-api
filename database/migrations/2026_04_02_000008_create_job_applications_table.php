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
        Schema::create('job_applications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('job_hiring_post_id')->nullable()->constrained('job_hiring_posts')->nullOnDelete();
            $table->foreignId('applicant_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('barangay', 160)->index();
            $table->string('job_title', 180);
            $table->string('company', 180);
            $table->string('posted_by', 180)->nullable();
            $table->string('applicant_name', 180);
            $table->string('mobile_number', 40);
            $table->text('cover_letter');
            $table->string('attachment_name', 255)->nullable();
            $table->string('status', 32)->default('Submitted');
            $table->timestamps();

            $table->index(['job_title', 'company']);
            $table->index(['applicant_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
