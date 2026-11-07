<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketEscalation extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'notified_users' => 'array',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function previousAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'previous_assigned_to');
    }

    public function newAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'new_assigned_to');
    }
}

