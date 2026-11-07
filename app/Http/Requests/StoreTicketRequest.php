<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('create_ticket');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeKb = config('ticket.attachments.max_size_kb', 10240);
        $allowedExtensions = implode(',', config('ticket.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']));
        $allowedMimes = implode(',', config('ticket.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'application/pdf']));

        return [
            'customer_id'   => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_type', User::TYPE_CUSTOMER);
                }),
            ],
            'assigned_to'   => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('account_type', User::TYPE_ADMIN);
                }),
            ],
            'subject'       => ['required', 'string', 'max:255'],
            'department'    => ['nullable', 'string', 'max:100'],
            'priority'      => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'description'   => ['required', 'string', 'min:2'],
            'attachments'   => ['nullable', 'array', 'max:' . $maxFiles],
            'attachments.*' => [
                'required',
                'file',
                'max:' . $maxSizeKb,
                'mimes:' . $allowedExtensions,
                'mimetypes:' . $allowedMimes,
            ],
            'media_ids'     => ['nullable', 'array', 'max:' . $maxFiles],
            'media_ids.*'   => ['integer', 'exists:media,id'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeMb = round(config('ticket.attachments.max_size_kb', 10240) / 1024);

        return [
            'customer_id.required'   => 'Please select a customer for this ticket.',
            'customer_id.exists'     => 'The selected user is not a valid customer account.',
            'assigned_to.exists'     => 'The selected assignee must be an internal staff account.',
            'subject.required'       => 'Please provide a ticket subject.',
            'priority.required'      => 'Please select a ticket priority.',
            'description.required'   => 'Please enter the initial message or description.',
            'description.min'        => 'Description must be at least 2 characters.',
            'attachments.max'        => "You can upload a maximum of {$maxFiles} attachments.",
            'attachments.*.max'      => "Each attachment must not exceed {$maxSizeMb}MB.",
            'attachments.*.mimes'    => 'Attachment format is not allowed.',
            'attachments.*.mimetypes' => 'Attachment file type is invalid or unsafe.',
        ];
    }
}

