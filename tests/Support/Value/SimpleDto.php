<?php

namespace Tests\Support\Value;

class SimpleDto
{
    /**
     * @param string $name
     * @param int $age
     * @param \Tests\Support\Value\Role $role
     * @param array{object: array{first: string, second: string}, string: string} $items
     */
    public function __construct(
        public string $name,
        public int $age,
        public Role $role = Role::User,
        public array $items,
    ) {
    }
}