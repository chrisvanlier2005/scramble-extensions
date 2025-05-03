<?php

namespace Lier\ScrambleExtensions\Appendable;

use App\Http\Resources\AnonymousResourceCollection;
use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\Json\JsonResource;

class AppendsEachMethodCallExtension implements MethodReturnTypeExtension
{
    /**
     * Determine whether this extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType|string $type
     * @return bool
     */
    public function shouldHandle(ObjectType|string $type): bool
    {
        if (is_string($type)) {
            return is_a($type, JsonResource::class, true);
        }

        return $type->isInstanceOf(JsonResource::class);
    }

    /**
     * Get the return type for the given method call.
     *
     * @param \Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent $event
     * @return \Dedoc\Scramble\Support\Type\Type|null
     */
    public function getMethodReturnType(MethodCallEvent $event): ?Type
    {
        return match ($event->name) {
            'appendEach' => new Generic(
                name: AppendableJsonResourceToSchema::$anonymousResourceCollectionName,
                templateTypes: $event->getInstance()->templateTypes,
            ),
            'preserveQuery' => new Generic( // Otherwise it's `UnknownType`, causing an empty response.
                name: $event->getInstance()->name,
                templateTypes: $event->getInstance()->templateTypes,
            ),
            default => null,
        };
    }
}
