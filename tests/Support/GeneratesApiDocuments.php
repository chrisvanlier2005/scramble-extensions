<?php

namespace Tests\Support;

use Dedoc\Scramble\Generator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RuntimeException;

trait GeneratesApiDocuments
{
    /**
     * Generate API and return the specification for the given controller.
     *
     * @param string $controller
     * @return array<array-key, mixed>
     */
    protected function generateForInvokable(string $controller): array
    {
        $generator = $this->app->make(Generator::class);

        $name = class_basename($controller);

        Route::get('api/' . $name, $controller)->name($name);

        return $generator();
    }

}