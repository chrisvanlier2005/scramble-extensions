<?php

namespace Tests\Support\Resources;

use Closure;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection as BaseAnonymousResourceCollection;

final class AnonymousResourceCollection extends BaseAnonymousResourceCollection
{
    final public function appendEach(Closure $callback): self
    {
        return $this;
    }
}