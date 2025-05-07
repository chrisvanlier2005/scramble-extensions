<?php

namespace Tests\Support\Resources;

use Illuminate\Http\Request;

/**
 * @property-read \Tests\Support\Models\Post $resource
 */
class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->resource->title,
            'content' => $this->resource->content,
            'tags' => $this->whenNotNull($this->resource->tags),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}