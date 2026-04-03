<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_gov_agencies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('province', 100);
            $table->string('city_municipality', 100);
            $table->string('barangay', 100);
            $table->string('code', 20);
            $table->string('display_name', 180);
            $table->string('website', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['province', 'city_municipality', 'barangay', 'is_active'], 'official_gov_agencies_scope_active_idx');
            $table->index(['province', 'city_municipality', 'barangay', 'sort_order'], 'official_gov_agencies_scope_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_gov_agencies');
    }
};

