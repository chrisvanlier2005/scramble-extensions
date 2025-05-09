<?php

namespace Lier\ScrambleExtensions\Properties\PhpDoc;

use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\SelfType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyTypesFromPhpDocExtension implements PropertyTypeExtension
{
    /**
     * @var array<array-key, mixed>
     */
    public static array $cache;

    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return !$type instanceof SelfType
            && !$type->isInstanceOf(JsonResource::class);
    }

    /**
     * Get the property type from the given event.
     *
     * @param \Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent $event
     * @return \Dedoc\Scramble\Support\Type\Type|null
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        $info = $this->getClassInfo($event->getInstance());

        if (!$info->attributes->has($event->getName())) {
            return null;
        }

        /** @var \Lier\ScrambleExtensions\Properties\PhpDoc\PhpDocAttribute|null $attribute */
        $attribute = $info->attributes->get($event->getName());

        return $attribute?->type;
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Lier\ScrambleExtensions\Properties\PhpDoc\ModelInfo
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    private function getClassInfo(ObjectType $type): ModelInfo
    {
        return static::$cache[$type->name] ??= new ClassInfo($type->name)->handle();
    }
}