<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use App\Rules\UniqueTranslation;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        return [
            'role_ids' => ['array', function ($attribute, $value, $fail) {
                if (!empty($value)) {
                    $myLevel = auth()->user()->roleLevel();
                    $invalid = Role::whereIn('id', $value)->where('level', '<', $myLevel)->exists();
                    if ($invalid) {
                        $fail(__('role.cannot_assign_higher_role'));
                    }
                }
            }],
            'role_ids.*' => 'exists:roles,id',
            'name' => [
                'required',
                new UniqueTranslation(User::class, 'name', $this->route('user')?->id)
            ],
            'email' => [
                'required',
                'email',
                'unique:users,email,' . $this->route('user')?->id
            ],
            'status' => ['required'],
            'password' => array_merge(['nullable'], password_rules()),
            'password_confirm' => ['required_with:password', 'same:password'],
            'avatar_media_id' => ['nullable', 'integer', 'exists:media,id'],
        ];
    }
}
