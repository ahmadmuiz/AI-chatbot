<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('remember_token');
            $table->boolean('is_active')->default(true)->after('is_admin');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->timestamp('disabled_at')->nullable()->after('must_change_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'is_active', 'must_change_password', 'disabled_at']);
        });
    }
};
