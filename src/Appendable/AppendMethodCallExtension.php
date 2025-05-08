<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\InferExtensions\JsonResourceExtension;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;
use UnexpectedValueException;

class AppendMethodCallExtension extends JsonResourceExtension implements MethodReturnTypeExtension
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
            'append' => $event->getInstance() instanceof Generic
                ? tap($event->getInstance(), function (Generic $type) use ($event) {
                    $arg = $event->getArg('additional', 0);

                    if (!$arg instanceof KeyedArrayType) {
                        throw new UnexpectedValueException(sprintf(
                            'Invalid type, expected %s, got %s',
                            KeyedArrayType::class,
                            get_debug_type($type)
                        ));
                    }

                    $type->templateTypes = [
                        ...$type->templateTypes,
                        new TaggedKeyedArrayType(
                            tag: 'append',
                            items: $arg->items,
                            isList: $arg->isList,
                        ),
                    ];
                })
                : null,
            'additional' => $event->getInstance() instanceof Generic
                ? tap($event->getInstance(), function (Generic $type) use ($event) {
                    $arg = $event->getArg('data', 0);

                    if (!$arg instanceof KeyedArrayType) {
                        throw new UnexpectedValueException(sprintf(
                            'Invalid type, expected %s, got %s',
                            KeyedArrayType::class,
                            get_debug_type($type)
                        ));
                    }

                    $type->templateTypes = [
                        ...$type->templateTypes,
                        new TaggedKeyedArrayType(
                            tag: 'additional',
                            items: $arg->items,
                            isList: $arg->isList,
                        ),
                    ];
                })
                : null,
            default => null,
        };
    }
}
