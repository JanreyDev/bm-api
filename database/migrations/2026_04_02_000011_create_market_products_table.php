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
        Schema::create('market_products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('merchant_registration_id')->nullable()->constrained('merchant_registrations')->nullOnDelete();
            $table->string('barangay', 160)->index();
            $table->string('title', 180);
            $table->string('seller_name', 180);
            $table->decimal('price', 12, 2);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->text('description');
            $table->string('category', 120)->index();
            $table->integer('stock')->default(0);
            $table->string('eta', 180);
            $table->boolean('verified')->default(false);
            $table->string('seller_zone', 80)->nullable();
            $table->string('seller_purok', 80)->nullable();
            $table->unsignedInteger('sold')->default(0);
            $table->unsignedInteger('reviews')->default(0);
            $table->decimal('rating', 3, 2)->default(4.8);
            $table->string('image_asset', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('market_products');
    }
};
