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
            'media_ids'     => ['nullable', 'array', 'max:' . $maxFiles],
            'media_ids.*'   => ['integer', 'exists:media,id'],
        ];
    }

    public function messages(): array
    {
        $maxFiles = config('ticket.attachments.max_files', 5);
        $maxSizeMb = round(config('ticket.attachments.max_size_kb', 10240) / 1024);

        return [
            'content.required'       => 'Please enter a reply message.',
            'content.min'            => 'Your reply must be at least 2 characters.',
            'attachments.max'        => "You can upload a maximum of {$maxFiles} attachments.",
            'attachments.*.max'      => "Each attachment must not exceed {$maxSizeMb}MB.",
            'attachments.*.mimes'    => 'Attachment format is not allowed.',
            'attachments.*.mimetypes' => 'Attachment file type is invalid or unsafe.',
        ];
    }
}
