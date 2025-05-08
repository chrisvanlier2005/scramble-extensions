<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;
use Lier\ScrambleExtensions\Support\Types\TaggedFunctionType;
use UnexpectedValueException;

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
            'appendEach' => $event->getInstance() instanceof Generic
                ? tap($event->getInstance(), function (Generic $type) use ($event) {
                    $arg = $event->getArg('callback', 0);

                    if (!$arg instanceof FunctionType) {
                        throw new InvalidArgumentException(sprintf(
                            'Invalid type, expected %s, got %s',
                            FunctionType::class,
                            get_debug_type($type)
                        ));
                    }

                    $type->templateTypes = [
                        ...$type->templateTypes,
                        new TaggedFunctionType(
                            tag: 'appendEach',
                            name: $arg->name,
                            arguments: $arg->arguments,
                            returnType: $arg->returnType,
                        )
                    ];
                })
                : null,
            'preserveQuery' => $event->getInstance(),
            default => null,
        };
    }
}
