<?php

namespace Tests\Support\Controllers;

use Illuminate\Http\JsonResponse;
use Tests\Support\Resources\DtoResource;
use Tests\Support\Value\Role;
use Tests\Support\Value\SimpleDto;

class DtoResourceController
{
    /**
     * Dto response
     *
     * Displays a JSON resource with a SimpleDto object.
     */
    public function __invoke(): DtoResource
    {
        return new DtoResource(new SimpleDto('test', 19, Role::Admin));
    }
}