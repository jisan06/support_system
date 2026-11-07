<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated customer accounts can access.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCustomer();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'avatar'                => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_avatar'         => ['nullable', 'boolean'],
            'current_password'      => ['nullable', 'required_with:password', 'current_password'],
            'password'              => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'required_with:password'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required'             => 'Please enter your full name.',
            'email.required'            => 'Please provide your email address.',
            'email.email'               => 'Please enter a valid email address.',
            'email.unique'              => 'This email address is already in use by another account.',
            'avatar.image'              => 'The profile photo must be a valid image file.',
            'avatar.mimes'              => 'The profile photo must be a file of type: jpeg, png, jpg, webp.',
            'avatar.max'                => 'The profile photo must not exceed 2MB in size.',
            'current_password.current_password' => 'The provided current password does not match our records.',
            'password.min'              => 'Your new password must be at least 8 characters.',
            'password.confirmed'        => 'The new password confirmation does not match.',
        ];
    }
}

