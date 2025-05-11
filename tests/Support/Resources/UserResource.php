<?php

namespace Tests\Support\Resources;

use Illuminate\Http\Request;

/**
 * @property-read \Tests\Support\Models\User $resource
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->resource->name,
            'role' => $this->resource->role,
            'money' => $this->resource->money,
        ];
    }

    public function with(Request $request)
    {
        return [
            'authorizations' => [
                'users' => [
                    /** @var bool */
                    'view' => $request->user()->can('view', $this->resource),
                    /** @var bool */
                    'update' => $request->user()->can('update', $this->resource),
                    /** @var bool */
                    'delete' => $request->user()->can('delete', $this->resource),
                ],
            ],
        ];
    }
}