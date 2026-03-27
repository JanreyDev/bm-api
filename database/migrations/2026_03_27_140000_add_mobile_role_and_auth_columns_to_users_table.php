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
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 20)->nullable()->after('email');
            $table->string('role', 20)->default('resident')->after('mobile');
            $table->string('middle_name')->nullable()->after('name');
            $table->string('suffix', 50)->nullable()->after('middle_name');
            $table->string('religion', 100)->nullable()->after('suffix');
            $table->string('province', 100)->nullable()->after('religion');
            $table->string('city_municipality', 100)->nullable()->after('province');
            $table->string('barangay', 100)->nullable()->after('city_municipality');
            $table->boolean('activation_completed')->default(false)->after('barangay');
            $table->string('otp_code', 6)->nullable()->after('activation_completed');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->timestamp('otp_verified_at')->nullable()->after('otp_expires_at');
            $table->string('api_token', 64)->nullable()->unique()->after('remember_token');
            $table->unique(['mobile', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_mobile_role_unique');
            $table->dropColumn([
                'mobile',
                'role',
                'middle_name',
                'suffix',
                'religion',
                'province',
                'city_municipality',
                'barangay',
                'activation_completed',
                'otp_code',
                'otp_expires_at',
                'otp_verified_at',
                'api_token',
            ]);
        });
    }
};
