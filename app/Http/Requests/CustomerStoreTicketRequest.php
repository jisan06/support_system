<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerStoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Only authenticated customers can submit customer tickets.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isCustomer();
    }

    /**
     * Prepare data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('category') && !$this->has('department')) {
            $this->merge(['department' => $this->input('category')]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $categories = config('ticket.categories', [
            'Technical Support',
            'Billing & Payments',
            'Account & Access',
            'General Inquiry',
        ]);
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeKb = config('ticket.attachments.max_size_kb', 10240);
        $allowedExtensions = implode(',', config('ticket.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']));
        $allowedMimes = implode(',', config('ticket.attachments.allowed_mimes', [
            'image/jpeg', 'image/png', 'image/webp', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain', 'application/zip', 'application/x-zip-compressed',
        ]));

        return [
            'subject'       => ['required', 'string', 'max:255'],
            'department'    => ['nullable', 'string', Rule::in($categories)],
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
            'department.in'           => 'The selected category is invalid.',
            'subject.required'        => 'Please provide a ticket subject.',
            'priority.required'       => 'Please select a ticket priority.',
            'description.required'    => 'Please describe your request or issue.',
            'description.min'         => 'Description must be at least 2 characters.',
            'attachments.max'         => "You can upload a maximum of {$maxFiles} attachments.",
            'attachments.*.max'       => "Each attachment must not exceed {$maxSizeMb}MB.",
            'attachments.*.mimes'     => 'Attachment format is not allowed.',
            'attachments.*.mimetypes' => 'Attachment file type is invalid or unsafe.',
        ];
    }
}

