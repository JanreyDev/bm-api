<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->string('post_type', 40)->default('social')->index()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('community_posts', function (Blueprint $table): void {
            $table->dropIndex(['post_type']);
            $table->dropColumn('post_type');
        });
    }
};

