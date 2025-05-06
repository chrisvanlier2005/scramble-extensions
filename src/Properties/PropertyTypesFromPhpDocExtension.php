<?php

namespace Lier\ScrambleExtensions\Properties;

use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Database\Eloquent\Model;

class PropertyTypesFromPhpDocExtension implements PropertyTypeExtension
{
    /**
     * @var array<array-key, mixed>
     */
    public static array $modelCache;

    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return $type->isInstanceOf(Model::class);
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

        $info = $this->getModelInfo($event->getInstance());

        if (!$info->attributes->has($event->getName())) {
            return null;
        }

        /** @var \Lier\ScrambleExtensions\Properties\PhpDocAttribute|null $attribute */
        $attribute = $info->attributes->get($event->getName());

        return $attribute?->type;
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Lier\ScrambleExtensions\Properties\ModelInfo
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    private function getModelInfo(ObjectType $type): ModelInfo
    {
        return static::$modelCache[$type->name] ??= new ModelInfoWithPhpDoc($type->name)->handle();
    }
}