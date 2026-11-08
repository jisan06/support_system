<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketImport extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'total_rows'      => 'integer',
            'processed_rows'  => 'integer',
            'successful_rows' => 'integer',
            'failed_rows'     => 'integer',
            'duplicate_rows'  => 'integer',
            'errors'          => 'array',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function progressPercentage(): int
    {
        if ($this->total_rows <= 0) {
            return $this->isCompleted() ? 100 : 0;
        }

        return (int) min(100, round(($this->processed_rows / $this->total_rows) * 100));
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'completed'  => 'bg-success',
            'processing' => 'bg-primary',
            'pending'    => 'bg-warning text-dark',
            'failed'     => 'bg-danger',
            default      => 'bg-secondary',
        };
    }

    /**
     * Activity logs recorded for this import.
     */
    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(\App\Models\ActivityLog::class, 'subject');
    }
}

