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
        Schema::table('market_products', function (Blueprint $table): void {
            if (!Schema::hasColumn('market_products', 'thumbnail_base64')) {
                $table->longText('thumbnail_base64')->nullable()->after('image_asset');
            }
            if (!Schema::hasColumn('market_products', 'thumbnail_file_name')) {
                $table->string('thumbnail_file_name', 255)->nullable()->after('thumbnail_base64');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('market_products', function (Blueprint $table): void {
            if (Schema::hasColumn('market_products', 'thumbnail_file_name')) {
                $table->dropColumn('thumbnail_file_name');
            }
            if (Schema::hasColumn('market_products', 'thumbnail_base64')) {
                $table->dropColumn('thumbnail_base64');
            }
        });
    }
};

