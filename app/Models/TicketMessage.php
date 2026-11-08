<?php

namespace App\Models;

use App\Enums\TicketMessageType;
use App\Models\Traits\HasMedia;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TicketMessage extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, HasMedia;

    public const TYPE_REPLY = TicketMessageType::REPLY->value;
    public const TYPE_INTERNAL_NOTE = TicketMessageType::INTERNAL_NOTE->value;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'type' => TicketMessageType::class,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        $isInternal = $this->isInternalNote()
            || ($this->getRawOriginal('type') === TicketMessageType::INTERNAL_NOTE->value)
            || ($this->getRawOriginal('type') === TicketMessageType::INTERNAL_NOTE);

        $logAttributes = ['ticket_id', 'user_id', 'type'];

        // Only log content for public replies.
        // Confidential internal notes must NEVER store raw content in activity_log.properties.
        if (!$isInternal) {
            $logAttributes[] = 'content';
        }

        return LogOptions::defaults()
            ->logOnly($logAttributes)
            ->logOnlyDirty()
            ->setDescriptionForEvent(function (string $eventName) use ($isInternal) {
                if ($isInternal) {
                    return "Ticket internal note was {$eventName}";
                }
                return "Ticket reply was {$eventName}";
            })
            ->useLogName('ticket_message');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Attachments associated with this ticket message.
     */
    public function attachments(): MorphMany
    {
        return $this->mediaByCollection(config('ticket.attachments.collection', 'ticket_attachments'));
    }

    /**
     * Scope query to public replies only.
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->where('type', TicketMessageType::REPLY->value);
    }

    /**
     * Scope query to internal notes only.
     */
    public function scopeInternalNotes(Builder $query): Builder
    {
        return $query->where('type', TicketMessageType::INTERNAL_NOTE->value);
    }

    /**
     * Scope query to messages visible to the given user.
     * Customers MUST never see internal notes.
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (!$user || $user->isCustomer()) {
            return $query->where('type', TicketMessageType::REPLY->value);
        }

        return $query;
    }

    public function isReply(): bool
    {
        return $this->type === TicketMessageType::REPLY || $this->type === TicketMessageType::REPLY->value;
    }

    public function isInternalNote(): bool
    {
        return $this->type === TicketMessageType::INTERNAL_NOTE || $this->type === TicketMessageType::INTERNAL_NOTE->value;
    }
}

