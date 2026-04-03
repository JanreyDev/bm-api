<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name', 160);
            $table->string('barangay', 120);
            $table->string('channel', 80)->default('General');
            $table->text('message');
            $table->boolean('is_official')->default(false);
            $table->timestamps();

            $table->index(['barangay', 'channel']);
            $table->index(['barangay', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_chat_messages');
    }
};
