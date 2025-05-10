<?php

namespace Lier\ScrambleExtensions\Resources;

use Dedoc\Scramble\Infer\Extensions\Event\MethodCallEvent;
use Dedoc\Scramble\Infer\Extensions\MethodReturnTypeExtension;
use Dedoc\Scramble\Support\Type\BooleanType;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\SelfType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Http\Resources\MergeValue;

class MergeWhenCallExtension implements MethodReturnTypeExtension
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
            return false;
        }

        return $type instanceof SelfType;
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
            'mergeWhen', 'mergeUnless', 'whenIncludes' => new Generic(MergeValue::class, [
                new BooleanType,
                $this->value($event->getArg('value', 1)),
            ]),
            default => null,
        };
    }

    private function value(Type $type)
    {
        return $type instanceof FunctionType ? $type->getReturnType() : $type;
    }
}
