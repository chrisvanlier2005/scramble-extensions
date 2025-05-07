<?php

namespace Tests;

use Dedoc\Scramble\ScrambleServiceProvider;
use Lier\ScrambleExtensions\ScrambleExtensionsServiceProvider;
use Orchestra\Testbench;
use Tests\Support\Provider\ScrambleTestProvider;

abstract class TestCase extends Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [
            ScrambleTestProvider::class,
            ScrambleExtensionsServiceProvider::class,
            ScrambleServiceProvider::class,
        ];
    }
}