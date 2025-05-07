<?php

namespace Tests\Support\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

class IndexProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'names' => [
                'filled',
                'array',
            ],
            'names.*' => [
                'string',
            ],
            'string_rule' => 'string|max:255',
            /** @example 'john doe' */
            'array_rule' => ['string', 'max:255', 'min:1'],
        ];
    }
}