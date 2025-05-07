<?php

namespace Tests\Support\Resources;

use Illuminate\Http\Resources\Json\JsonResource as BaseJsonResource;

/**
 * @method static \Tests\Support\Resources\AnonymousResourceCollection collection($resource)
 */
class JsonResource extends BaseJsonResource
{
    final public function append(array $additional): static
    {
        return $this;
    }
}