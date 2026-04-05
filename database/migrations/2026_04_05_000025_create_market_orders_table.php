<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('barangay', 120)->index();
            $table->string('order_code', 64);
            $table->string('status', 40)->default('Pending')->index();
            $table->string('payer_name', 180);
            $table->string('payer_mobile', 40)->index();
            $table->text('payer_address')->nullable();
            $table->string('payment_provider', 60)->default('GCash');
            $table->text('payment_link')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->json('items_json');
            $table->timestamps();

            $table->unique(['user_id', 'order_code'], 'market_orders_user_code_unique');
            $table->index(['barangay', 'created_at'], 'market_orders_barangay_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_orders');
    }
};
