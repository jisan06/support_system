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
            $table->unsignedSmallInteger('escalation_level')->default(0)->after('resolved_at')->index();
            $table->timestamp('last_escalated_at')->nullable()->after('escalation_level')->index();
        });

        Schema::create('ticket_escalations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->unsignedSmallInteger('level')->index();
            $table->string('trigger_reason', 100)->index();
            $table->foreignId('previous_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('new_assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action_taken', 50);
            $table->json('notified_users')->nullable();
            $table->timestamps();

            $table->index(['ticket_id', 'level', 'trigger_reason']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_escalations');

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['escalation_level', 'last_escalated_at']);
        });
    }
};

