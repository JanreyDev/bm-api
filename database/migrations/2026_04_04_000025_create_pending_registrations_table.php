<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('mobile', 20);
            $table->string('role', 20);
            $table->string('otp_code', 6);
            $table->timestamp('otp_expires_at')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['mobile', 'role']);
            $table->index('otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};

