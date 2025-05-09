<?php

namespace Tests\Support\Resources;

use Illuminate\Http\Request;

/**
 * @property-read \Tests\Support\Value\SimpleDto $resource
 */
class DtoResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'age' => $this->resource->age,
            'role' => $this->resource->role,
            'items' => $this->resource->items,
        ];
    }
}