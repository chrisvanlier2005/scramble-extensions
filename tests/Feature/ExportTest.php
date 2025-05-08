<?php

namespace Tests\Feature;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Lier\ScrambleExtensions\Commands\DocsExport;
use Lier\ScrambleExtensions\ExtensionRegistry;
use RuntimeException;
use Tests\Support\Controllers\PaginatedProductController;
use Tests\Support\Controllers\ProductControllerWithComplexFilters;
use Tests\Support\Controllers\UserControllerWithAppends;
use Tests\TestCase;

final class ExportTest extends TestCase
{
    private string $temporaryFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->temporaryFile = tempnam(sys_get_temp_dir(), '')
            ?: throw new RuntimeException();
    }

    protected function tearDown(): void
    {
        @unlink($this->temporaryFile);

        parent::tearDown();
    }
    
    public function testItExports(): void
    {
        $this->registerRoutes();

        Artisan::call(DocsExport::class, [
            '--path' => $this->temporaryFile,
        ]);

        $this->assertFileExists($this->temporaryFile);

        $this->assertFileEquals(
            __DIR__ . '/../data/expected.yml',
            $this->temporaryFile,
        );
    }

    private function registerRoutes(): void
    {
        Route::prefix('api')->name('test.')->group(function () {
            Route::resource('basic-user-crud', UserControllerWithAppends::class);
            Route::get('list-products', PaginatedProductController::class)->name('list-products');
            Route::get('products-with-filters', ProductControllerWithComplexFilters::class)
                ->name('products-with-filters');
        });
    }
}