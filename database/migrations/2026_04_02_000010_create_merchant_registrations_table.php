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
        Schema::create('merchant_registrations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('barangay', 160)->index();
            $table->string('business_name', 180);
            $table->string('owner_name', 180);
            $table->string('business_type', 120);
            $table->string('contact_number', 40);
            $table->string('address', 500);
            $table->string('meetup_spot', 180);
            $table->string('business_permit_number', 80);
            $table->string('business_permit_file_name', 255);
            $table->longText('business_permit_image_base64')->nullable();
            $table->boolean('merchant_verified')->default(false)->index();
            $table->string('verification_status', 40)->default('Pending Review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_registrations');
    }
};
