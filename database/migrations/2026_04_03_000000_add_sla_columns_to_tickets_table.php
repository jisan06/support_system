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
        Schema::table('tickets', function (Blueprint $table) {
            $table->timestamp('first_response_due_at')->nullable()->after('last_reply_at')->index();
            $table->timestamp('resolution_due_at')->nullable()->after('first_response_due_at')->index();
            $table->timestamp('first_responded_at')->nullable()->after('resolution_due_at')->index();
            $table->timestamp('resolved_at')->nullable()->after('first_responded_at')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['first_response_due_at']);
            $table->dropIndex(['resolution_due_at']);
            $table->dropIndex(['first_responded_at']);
            $table->dropIndex(['resolved_at']);
            $table->dropColumn([
                'first_response_due_at',
                'resolution_due_at',
                'first_responded_at',
                'resolved_at',
            ]);
        });
    }
};

