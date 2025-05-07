<?php

namespace Tests\Support\Controllers;

use Tests\Support\Models\Product;
use Tests\Support\Requests\Products\IndexProductRequest;
use Tests\Support\Resources\AnonymousResourceCollection;
use Tests\Support\Resources\ProductResource;

class ProductControllerWithComplexFilters
{
    /**
     * List products
     *
     * Displays a listing of products.
     */
    public function __invoke(IndexProductRequest $request): AnonymousResourceCollection
    {
        $products = Product::query()
            ->with(['category', 'tags'])
            ->latest()
            ->get();

        return ProductResource::collection($products);
    }
}