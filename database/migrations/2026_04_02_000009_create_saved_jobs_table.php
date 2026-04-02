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
        Schema::create('saved_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('job_hiring_post_id')->nullable()->constrained('job_hiring_posts')->nullOnDelete();
            $table->string('barangay', 160)->index();
            $table->string('job_title', 180);
            $table->string('company', 180);
            $table->timestamps();

            $table->unique(['user_id', 'job_hiring_post_id']);
            $table->index(['user_id', 'job_title', 'company']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
    }
};
