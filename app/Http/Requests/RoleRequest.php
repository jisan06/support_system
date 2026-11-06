<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Rules\UniqueTranslation;
use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $myLevel = auth()->user()->roleLevel();
        $role = $this->route('role');

        // When editing own role, can't set level lower than current
        $minLevel = ($role && auth()->user()->roles->contains('id', $role->id))
            ? $role->level
            : $myLevel;

        return [
            'name' => [
                'required',
                new UniqueTranslation(Role::class, 'name', $role?->id)
            ],
            'level' => ['required', 'integer', 'min:' . $minLevel],
            'status' => ['required']
        ];
    }
}
