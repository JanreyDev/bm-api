<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('education_attainment', 160)->nullable();
            $table->string('employment_type', 120)->nullable();
            $table->string('employment_sector', 120)->nullable();
            $table->unsignedInteger('household_size')->nullable();
            $table->decimal('monthly_household_income', 12, 2)->nullable();
            $table->string('house_ownership_status', 120)->nullable();
            $table->json('utilities')->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->string('blood_type', 20)->nullable();
            $table->text('medical_notes')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_profiles');
    }
};
