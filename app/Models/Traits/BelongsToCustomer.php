<?php

namespace App\Models\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCustomer
{
    /**
     * Relationship to the customer owner.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id')
            ->where('account_type', User::TYPE_CUSTOMER);
    }

    /**
     * Scope query to tickets owned by a specific customer.
     */
    public function scopeForCustomer(Builder $query, int|User $customer): Builder
    {
        $customerId = $customer instanceof User ? $customer->id : $customer;

        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope query to the currently authenticated customer.
     * Prevents cross-customer data leakage.
     */
    public function scopeForCurrentCustomer(Builder $query): Builder
    {
        $user = auth()->user();

        if (!$user || !$user->isCustomer()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('customer_id', $user->id);
    }
}

