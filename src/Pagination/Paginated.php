<?php

namespace Lier\ScrambleExtensions\Pagination;

use Attribute;
use Illuminate\Pagination\LengthAwarePaginator;

#[Attribute(Attribute::TARGET_METHOD)]
class Paginated
{
    /**
     * @param class-string<\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator> $class
     * @return void
     */
    public function __construct(
        public string $class = LengthAwarePaginator::class,
    ) {
    }
}
