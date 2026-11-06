<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Native ENUM with default 'customer' as the least privileged classification
            $table->enum('account_type', ['admin', 'customer'])
                ->default('customer')
                ->after('email')
                ->index();
        });

        // Safe Backfill:
        // 1. Root / Super Admin (ID 1) created during installation is an internal admin
        DB::table('users')
            ->where('id', 1)
            ->update(['account_type' => 'admin']);

        // 2. Any user assigned a role in role_user is an internal user
        $assignedUserIds = DB::table('role_user')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        if (!empty($assignedUserIds)) {
            DB::table('users')
                ->whereIn('id', $assignedUserIds)
                ->update(['account_type' => 'admin']);
        }

        // 3. Existing users without roles remain 'customer'
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['account_type']);
            $table->dropColumn('account_type');
        });
    }
};

