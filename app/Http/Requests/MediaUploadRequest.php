<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedMimes = implode(',', config('media.allowed_mimes'));
        $maxSize = config('media.max_size_kb');

        return [
            'file' => "required|file|mimes:{$allowedMimes}|max:{$maxSize}",
            'collection' => 'nullable|string|max:255',
        ];
    }
}
