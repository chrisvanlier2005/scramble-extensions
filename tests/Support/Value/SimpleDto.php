<?php

namespace Tests\Support\Value;

class SimpleDto
{
    public function __construct(
        public string $name,
        public int $age,
    ) {
    }
}