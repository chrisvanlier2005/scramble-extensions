<?php

namespace Tests\Feature\Appendable;

use Illuminate\Http\Resources\MissingValue;
use Spatie\Snapshots\MatchesSnapshots;
use Tests\Support\GeneratesApiDocuments;
use Tests\Support\Resources\PostResource;
use Tests\Support\Resources\UserResource;
use Tests\TestCase;

final class AppendableJsonResourceToSchemaTest extends TestCase
{
    use MatchesSnapshots;
    use GeneratesApiDocuments;

    public function testItGenerates(): void
    {
        $documentation = $this->generateForInvokable(Simple_Append_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }

    public function testItGeneratesWithNestedAppend(): void
    {
        $documentation = $this->generateForInvokable(Nested_Append_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }

    public function testItDoesNotMarkAsRequiredWhenResourceConstructorIsCoalescedWithMissingValue(): void
    {
        $documentation = $this->generateForInvokable(Coalesced_Append_Controller::class);

        $this->assertMatchesJsonSnapshot($documentation);
    }
}

/**
 * @internal
 */
class Simple_Append_Controller
{
    public function __invoke(): UserResource
    {
        $user = User::query()->firstOrFail();

        return new UserResource($user)->append([
            'has_posts' => $user->posts_exists,
            /** @var bool */
            'has_posts_with_correct_type' => $user->posts_exists,
        ]);
    }
}

/**
 * @internal
 */
class Nested_Append_Controller
{
    public function __invoke(): UserResource
    {
        $user = User::query()->firstOrFail();
        $post = $user->posts->first();

        return new UserResource($user)->append([
            'post' => new PostResource($post)->append([
                'something' => 'something',
                'something_else' => 1234,
            ]),
        ]);
    }
}

/**
 * @internal
 */
class Coalesced_Append_Controller
{
    public function __invoke(): UserResource
    {
        $user = User::query()->firstOrFail();
        $post = $user->posts->first();

        return new UserResource($user)->append([
            'post' => new PostResource($post ?? new MissingValue())->append([
                'author' => new UserResource($user ?? new MissingValue()),
                'manager' => new UserResource($user),
            ])
        ]);
    }
}