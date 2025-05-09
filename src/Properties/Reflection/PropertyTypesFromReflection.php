<?php

namespace Lier\ScrambleExtensions\Properties\Reflection;

use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

class PropertyTypesFromReflection implements PropertyTypeExtension
{

    public function shouldHandle(ObjectType $type): bool
    {
        return false;
    }

    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        return null;
    }
}