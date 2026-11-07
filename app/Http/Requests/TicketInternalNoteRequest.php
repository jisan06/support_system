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
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeKb = config('ticket.attachments.max_size_kb', 10240);
        $allowedExtensions = implode(',', config('ticket.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip']));
        $allowedMimes = implode(',', config('ticket.attachments.allowed_mimes', ['image/jpeg', 'image/png', 'application/pdf']));

        return [
            'content'       => ['required', 'string', 'min:2', 'max:10000'],
            'attachments'   => ['nullable', 'array', 'max:' . $maxFiles],
            'attachments.*' => [
                'required',
                'file',
                'max:' . $maxSizeKb,
                'mimes:' . $allowedExtensions,
                'mimetypes:' . $allowedMimes,
            ],
        ];
    }

    public function messages(): array
    {
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeMb = round(config('ticket.attachments.max_size_kb', 10240) / 1024);

        return [
            'content.required'       => 'Please enter an internal note.',
            'content.min'            => 'Internal note must be at least 2 characters.',
            'attachments.max'        => "You can upload a maximum of {$maxFiles} attachments.",
            'attachments.*.max'      => "Each attachment must not exceed {$maxSizeMb}MB.",
            'attachments.*.mimes'    => 'Attachment format is not allowed.',
            'attachments.*.mimetypes' => 'Attachment file type is invalid or unsafe.',
        ];
    }
}
