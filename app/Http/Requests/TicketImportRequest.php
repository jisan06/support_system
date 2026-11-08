<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketImportRequest extends FormRequest
{
    /**
     * Determine if user is authorized to perform ticket import.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasPermissionTo('create_ticket');
    }

    /**
     * Validation rules for CSV upload.
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:20480', // 20 MB max
            ],
        ];
    }

    /**
     * Custom error messages.
     */
    public function messages(): array
    {
        return [
            'csv_file.required' => 'Please select a CSV file to upload.',
            'csv_file.file'     => 'The uploaded file is not valid.',
            'csv_file.mimes'    => 'The file must be a CSV file (.csv).',
            'csv_file.max'      => 'The CSV file size may not exceed 20MB.',
        ];
    }
}

