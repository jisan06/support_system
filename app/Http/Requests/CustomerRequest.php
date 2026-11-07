<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Rules\UniqueTranslation;
use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('customer') instanceof User
            ? $this->route('customer')->id
            : $this->route('customer');

        $isCreate = $this->isMethod('POST');

        return [
            'name' => [
                'required',
                new UniqueTranslation(User::class, 'name', $userId)
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $userId,
            ],
            'status' => ['required', 'in:Active,Inactive'],
            'password' => $isCreate
                ? array_merge(['required'], password_rules())
                : array_merge(['nullable'], password_rules()),
            'password_confirm' => $isCreate
                ? ['required', 'same:password']
                : ['nullable', 'required_with:password', 'same:password'],
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}

