<?php

namespace Tests\Support\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property string $title
 * @property string $content
 * @property array<string>|null $tags
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 * @property-read \Tests\Support\Models\User|null $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Tests\Support\Models\User> $likedByUsers
 */
class Post extends Model
{
    //
}