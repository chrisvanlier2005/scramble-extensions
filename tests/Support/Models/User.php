<?php

namespace Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $name
 * @property \Tests\Support\Value\Role $role
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Tests\Support\Models\Post> $posts
 * @property-read bool $posts_exists
 */
class User extends Model
{
    //
}