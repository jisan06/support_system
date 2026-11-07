<?php

namespace App\Models;

use App\Enums\TicketMessageType;
use App\Models\Traits\BelongsToCustomer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Ticket extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, BelongsToCustomer;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'last_reply_at'          => 'datetime',
            'first_response_due_at'  => 'datetime',
            'resolution_due_at'      => 'datetime',
            'first_responded_at'     => 'datetime',
            'resolved_at'            => 'datetime',
            'last_escalated_at'      => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['ticket_number', 'customer_id', 'assigned_to', 'subject', 'status', 'priority', 'escalation_level'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName) => "Ticket {$this->ticket_number} was {$eventName}")
            ->useLogName('ticket');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at', 'asc');
    }

    public function replies(): HasMany
    {
        return $this->messages()->where('type', TicketMessageType::REPLY->value);
    }

    public function internalNotes(): HasMany
    {
        return $this->messages()->where('type', TicketMessageType::INTERNAL_NOTE->value);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(TicketEscalation::class, 'ticket_id')->orderBy('level', 'asc');
    }

    /**
     * Scope query to active tickets.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    /**
     * Get first response SLA status.
     */
    public function getFirstResponseSlaStatusAttribute(): \App\Enums\SlaStatus
    {
        return app(\App\Services\SlaService::class)->getFirstResponseStatus($this);
    }

    /**
     * Get resolution SLA status.
     */
    public function getResolutionSlaStatusAttribute(): \App\Enums\SlaStatus
    {
        return app(\App\Services\SlaService::class)->getResolutionStatus($this);
    }

    /**
     * Get overall ticket SLA status.
     */
    public function getSlaStatusAttribute(): \App\Enums\SlaStatus
    {
        return app(\App\Services\SlaService::class)->getOverallStatus($this);
    }
}

