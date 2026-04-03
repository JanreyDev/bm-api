<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_shared_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('province', 120)->index();
            $table->string('city_municipality', 120)->index();
            $table->string('barangay', 120)->index();
            $table->string('address', 255);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->boolean('high_accuracy')->default(true);
            $table->boolean('include_landmark')->default(true);
            $table->timestamp('shared_at')->useCurrent()->index();
            $table->timestamps();

            $table->index(
                ['province', 'city_municipality', 'barangay', 'shared_at'],
                'emergency_shared_locations_scope_idx'
            );
            $table->index(['user_id', 'shared_at'], 'emergency_shared_locations_user_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emergency_shared_locations');
    }
};

