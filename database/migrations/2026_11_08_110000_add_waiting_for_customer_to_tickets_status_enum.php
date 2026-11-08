<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tickets` MODIFY COLUMN `status` ENUM('open', 'in_progress', 'waiting_for_customer', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `tickets` MODIFY COLUMN `status` ENUM('open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'open'");
        }
    }
};

