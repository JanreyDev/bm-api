<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            if (!Schema::hasColumn('job_applications', 'attachment_base64')) {
                $table->longText('attachment_base64')->nullable()->after('attachment_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            if (Schema::hasColumn('job_applications', 'attachment_base64')) {
                $table->dropColumn('attachment_base64');
            }
        });
    }
};

