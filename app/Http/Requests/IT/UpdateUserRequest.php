<?php

namespace App\Http\Requests\IT;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'division_id' => ['nullable', 'exists:divisions,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'is_active' => ['sometimes', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'modules' => ['sometimes', 'array'],
            'modules.*' => ['exists:modules,id'],
            'page_access' => ['sometimes', 'array'],
            'page_access.*' => ['in:view,edit'],
        ];
    }
}
