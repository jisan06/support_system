<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketInternalNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only internal staff/admin accounts can submit internal notes.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
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
            'content.required' => 'Please enter an internal note.',
            'content.min'      => 'Internal note must be at least 2 characters.',
        ];
    }
}

