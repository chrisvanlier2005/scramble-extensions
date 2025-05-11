<?php

namespace Tests\Support\Controllers;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Response;
use Tests\Support\Models\Post;
use Tests\Support\Models\User;
use Tests\Support\Requests\Users\StoreUserRequest;
use Tests\Support\Requests\Users\UpdateUserRequest;
use Tests\Support\Resources\PostResource;
use Tests\Support\Resources\UserResource;

class UserControllerWithAppends
{
    public function index(): AnonymousResourceCollection
    {
        $user = User::query()->paginate();

        return UserResource::collection($user)
            ->appendEach(fn (User $user) => [
                'posts' => PostResource::collection($user->posts)
                    ->appendEach(fn (Post $post) => [
                        'author' => UserResource::make($post->user ?? new MissingValue()),
                        'liked_by_users' => UserResource::collection($post->likedByUsers),
                    ]),
            ])
            ->additional([
                /** @var array<string> */
                'names' => $user->pluck('name'),
            ]);
    }

    public function show(User $user): UserResource
    {
        return UserResource::make($user)->append([
            /** @var \Tests\Support\Value\Role */
            'name' => $user->role,
        ]);
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $user = new User();
        $user->name = $request->validated('name');
        $user->save();

        /** @status 201 */
        return UserResource::make($user);
    }

    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $user->name = $request->validated('name');
        $user->save();

        return UserResource::make($user);
    }

    public function destroy(User $user): Response
    {
        $user->delete();

        return response()->noContent();
    }
}