<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Please enter a reply message.',
            'content.min'      => 'Your reply must be at least 2 characters.',
        ];
    }
}

