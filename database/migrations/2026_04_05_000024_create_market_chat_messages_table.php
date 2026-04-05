<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_chat_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sender_name', 180);
            $table->string('sender_role', 40)->default('buyer')->index();
            $table->string('barangay', 120)->index();
            $table->string('buyer_mobile', 40)->index();
            $table->string('buyer_name', 180)->nullable();
            $table->string('seller_name', 180)->index();
            $table->string('product_title', 200)->index();
            $table->text('message');
            $table->boolean('is_official')->default(false)->index();
            $table->timestamps();

            $table->index(
                ['barangay', 'buyer_mobile', 'seller_name', 'product_title'],
                'market_chat_thread_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_chat_messages');
    }
};
