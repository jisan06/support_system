<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
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
     * Dynamic rules based on the settings group config.
     */
    public function rules(): array
    {
        $group = $this->route('group');
        $fields = config("settings.{$group}.fields", []);

        $rules = [];
        foreach ($fields as $key => $field) {
            $rules[$key] = $field['rules'] ?? 'nullable|string';
        }

        return $rules;
    }
}