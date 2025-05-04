<?php

namespace Lier\ScrambleExtensions;

use Illuminate\Support\ServiceProvider;
use Lier\ScrambleExtensions\Commands\DocsExport;

class ScrambleExtensionsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(DocsExport::class);
        }
    }
}