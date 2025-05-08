<?php

namespace Tests\Support\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        /** @var \Tests\Support\Models\User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'prohibited_with_route_parameter' => [
                'nullable',
                Rule::prohibitedIf($user->posts_exists),
            ],
        ];
    }
}