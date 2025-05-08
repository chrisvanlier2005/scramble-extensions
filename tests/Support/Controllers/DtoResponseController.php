<?php

namespace Tests\Support\Controllers;

use Illuminate\Http\JsonResponse;
use Tests\Support\Value\SimpleDto;

class DtoResponseController
{
    /**
     * Dto response
     *
     * Displays a JSON response with a SimpleDto object, inference not supported without a custom-to-schema extension
     */
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => new SimpleDto('test', 19),
        ]);
    }
}