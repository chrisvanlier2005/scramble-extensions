<?php

namespace Tests\Feature\Appendable;

use Illuminate\Support\Str;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\Support\GeneratesApiDocuments;
use Tests\Support\Models\Post;
use Tests\Support\Models\User;
use Tests\Support\Resources\AnonymousResourceCollection;
use Tests\Support\Resources\PostResource;
use Tests\Support\Resources\UserResource;
use Tests\TestCase;

final class AppendableAnonymousResourceCollectionSchemaTest extends TestCase
{
    use MatchesSnapshots;
    use GeneratesApiDocuments;

    public function testItGeneratesForSimpleAppendEach(): void
    {
        $documentation = $this->generateForInvokable(Simple_Append_Each_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }

    public function testItGeneratesForNestedAppendEach(): void
    {
        $documentation = $this->generateForInvokable(Nested_Append_Each_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }

    public function testItGeneratesWithVarAnnotations(): void
    {
        $documentation = $this->generateForInvokable(Var_Annotated_Append_Each_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }
}

/**
 * @internal
 */
class Simple_Append_Each_Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $user = User::query()->get();

        return UserResource::collection($user)->appendEach(fn (User $user) => [
            'has_posts' => $user->posts_exists,
            // Too complex, requires @var annotation
            'full_name' => $user->name . ' ' . Str::uuid(),
        ]);
    }
}

/**
 * @internal
 */
class Nested_Append_Each_Controller {
    public function __invoke(): AnonymousResourceCollection
    {
        $user = User::query()->get();

        return UserResource::collection($user)->appendEach(fn (User $user) => [
            'posts' => PostResource::collection($user->posts)->appendEach(fn (Post $post) => [
                'user' => UserResource::make($post->user),
            ])
        ]);
    }
}

/**
 * @internal
 */
class Var_Annotated_Append_Each_Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        $user = User::query()->get();

        return UserResource::collection($user)->appendEach(fn (User $user) => [
            /** @var string */
            'name' => $user->name,
            /** @var array{tags: array<string>, title: string, likes: int} */
            'data' => [],
            'user' => UserResource::make($user), // no annotation.
        ]);
    }
}

