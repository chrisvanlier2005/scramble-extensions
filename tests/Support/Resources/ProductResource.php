<?php

namespace Tests\Support\Resources;

use Illuminate\Http\Request;

/**
 * @property-read \Tests\Support\Models\Product $resource
 */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'price' => $this->resource->price,
        ];
    }
}