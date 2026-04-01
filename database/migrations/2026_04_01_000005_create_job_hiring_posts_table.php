<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_hiring_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('barangay', 120)->index();
            $table->string('title', 180);
            $table->string('company', 180);
            $table->string('location', 180);
            $table->string('salary', 120);
            $table->string('schedule', 40);
            $table->text('requirements');
            $table->string('posted_by', 180);
            $table->boolean('urgent')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_hiring_posts');
    }
};
