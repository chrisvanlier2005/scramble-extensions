<?php

namespace Tests\Support\Controllers;

use Lier\ScrambleExtensions\Pagination\Paginated;
use Tests\Support\Models\Product;
use Tests\Support\Resources\AnonymousResourceCollection;
use Tests\Support\Resources\ProductResource;

class PaginatedProductController
{
    /**
     * List products
     *
     * Displays a listing of products.
     */
    #[Paginated]
    public function __invoke(): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(10);

        return ProductResource::collection($products);
    }
}