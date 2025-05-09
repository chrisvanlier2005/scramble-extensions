<?php

namespace Lier\ScrambleExtensions\Properties\PhpDoc;

use Illuminate\Support\Collection;

final readonly class ModelInfo
{
    /**
     * Create a new value object.
     *
     * @param \Illuminate\Support\Collection<string, \Lier\ScrambleExtensions\Properties\PhpDoc\PhpDocAttribute> $attributes
     * @return void
     */
    public function __construct(
        public Collection $attributes = new Collection(),
    ) {
    }
}
