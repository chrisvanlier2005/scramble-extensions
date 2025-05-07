<?php

namespace Tests\Support\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Tests\Support\Value\Role;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => [
                'required',
                Rule::enum(Role::class),
            ]
        ];
    }
}