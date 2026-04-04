<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_barangay_setups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('province', 100);
            $table->string('city_municipality', 100);
            $table->string('barangay', 100);
            $table->string('division_type', 30)->default('Zone');
            $table->unsignedInteger('division_count')->default(1);
            $table->unsignedSmallInteger('founding_year')->default(1900);
            $table->string('website', 255)->nullable();
            $table->string('facebook_url', 255)->nullable();
            $table->decimal('latitude', 10, 6)->nullable();
            $table->decimal('longitude', 10, 6)->nullable();
            $table->string('logo_file_name', 180)->nullable();
            $table->longText('logo_image_base64')->nullable();
            $table->timestamps();

            $table->unique(['province', 'city_municipality', 'barangay'], 'official_barangay_setup_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_barangay_setups');
    }
};
