<?php

namespace App\ViewModels\Admin;

use App\Models\User;

class CustomerFormData
{
    public function __construct(
        public readonly ?User $customer = null,
    ) {}

    public static function forCreate(): self
    {
        return new self();
    }

    public static function forEdit(User $customer): self
    {
        return new self(
            customer: $customer,
        );
    }

    public function toArray(): array
    {
        return [
            'customer' => $this->customer,
        ];
    }
}

