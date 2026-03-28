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
        Schema::create('barangay_locations', function (Blueprint $table) {
            $table->id();
            $table->string('province', 100);
            $table->string('city_municipality', 120);
            $table->string('barangay', 120);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['province', 'city_municipality', 'barangay'], 'barangay_locations_unique_address');
            $table->index(['province', 'city_municipality'], 'barangay_locations_city_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('barangay_locations');
    }
};

