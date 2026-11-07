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
        Schema::table('ticket_escalations', function (Blueprint $table) {
            $table->unique(['ticket_id', 'level', 'trigger_reason'], 'ticket_escalations_unique_level_trigger');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_escalations', function (Blueprint $table) {
            $table->dropUnique('ticket_escalations_unique_level_trigger');
        });
    }
};
