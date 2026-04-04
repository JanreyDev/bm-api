<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('source_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('province', 100)->default('');
            $table->string('city_municipality', 100)->default('');
            $table->string('barangay', 100)->default('');
            $table->string('title', 180);
            $table->string('body', 500);
            $table->string('category', 80)->default('System');
            $table->string('priority', 20)->default('normal');
            $table->string('record_type', 80)->nullable();
            $table->string('record_id', 80)->nullable();
            $table->string('deep_link', 255)->nullable();
            $table->json('metadata_json')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['province', 'city_municipality', 'barangay'], 'official_notifications_scope_idx');
            $table->index(['target_user_id', 'is_read'], 'official_notifications_target_read_idx');
            $table->index(['barangay', 'is_read', 'created_at'], 'official_notifications_barangay_read_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_notifications');
    }
};
