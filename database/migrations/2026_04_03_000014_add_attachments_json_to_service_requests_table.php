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
        Schema::table('service_requests', function (Blueprint $table): void {
            if (!Schema::hasColumn('service_requests', 'attachments_json')) {
                $table->longText('attachments_json')->nullable()->after('details');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('service_requests', 'attachments_json')) {
                $table->dropColumn('attachments_json');
            }
        });
    }
};

