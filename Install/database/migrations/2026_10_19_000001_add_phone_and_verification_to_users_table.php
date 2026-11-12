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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'country_code')) {
                    $table->string('country_code', 10)->nullable()->after('email');
                }
                if (!Schema::hasColumn('users', 'phone')) {
                    $table->string('phone', 30)->nullable()->after('country_code');
                }
                if (!Schema::hasColumn('users', 'phone_verified_at')) {
                    $table->timestamp('phone_verified_at')->nullable()->after('phone');
                }
                if (!Schema::hasColumn('users', 'email_verified_at')) {
                    $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'email_verified_at')) {
                    $table->dropColumn('email_verified_at');
                }
                if (Schema::hasColumn('users', 'phone_verified_at')) {
                    $table->dropColumn('phone_verified_at');
                }
                if (Schema::hasColumn('users', 'phone')) {
                    $table->dropColumn('phone');
                }
                if (Schema::hasColumn('users', 'country_code')) {
                    $table->dropColumn('country_code');
                }
            });
        }
    }
};