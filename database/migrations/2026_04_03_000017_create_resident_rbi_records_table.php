<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resident_rbi_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('rbi_id', 80)->nullable()->unique();
            $table->string('first_name', 120);
            $table->string('middle_name', 120)->nullable();
            $table->string('last_name', 120);
            $table->string('suffix', 50)->nullable();
            $table->string('province', 120)->nullable()->index();
            $table->string('city_municipality', 120)->nullable()->index();
            $table->string('barangay', 120)->nullable()->index();
            $table->string('street_name', 200)->nullable();
            $table->string('zone_purok', 120)->nullable();
            $table->unsignedSmallInteger('year_of_residency')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 60)->nullable();
            $table->string('disability_tag', 120)->nullable();
            $table->boolean('blood_donor_opt_in')->default(false);
            $table->string('blood_type', 20)->nullable();
            $table->string('education_aid_status', 120)->nullable();
            $table->string('latest_grade_average', 40)->nullable();
            $table->unsignedInteger('family_count')->default(0);
            $table->unsignedInteger('vehicle_count')->default(0);
            $table->unsignedInteger('vaccination_count')->default(0);
            $table->decimal('latest_bmi', 6, 2)->nullable();
            $table->unsignedTinyInteger('verification_step')->default(1);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resident_rbi_records');
    }
};

