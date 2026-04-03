<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('province', 120)->index();
            $table->string('city_municipality', 120)->index();
            $table->string('barangay', 120)->index();
            $table->string('label', 120);
            $table->string('phone_number', 60);
            $table->string('description', 220)->nullable();
            $table->boolean('quick_dial')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['province', 'city_municipality', 'barangay', 'is_active'], 'emergency_contacts_scope_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_contacts');
    }
};

