<?php

namespace Lier\ScrambleExtensions\Support;

use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Illuminate\Support\Collection;

class OpenApiObjectHelper
{
    /**
     * Create a new openapi object from an associative array.
     *
     * @param array<array-key, mixed> $array
     * @param array<string> $required
     * @return \Dedoc\Scramble\Support\Generator\Types\ObjectType
     */
    public static function createObjectTypeFromArray(array $array, array $required = []): ObjectType
    {
        $object = new ObjectType();

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $object->addProperty($key, self::createObjectTypeFromArray($value));
            } else {
                $object->addProperty($key, $value);
            }
        }

        $object->setRequired($required);

        return $object;
    }

    /**
     * @param \Illuminate\Support\Collection $collection
     * @param list<string> $requiredKeys
     * @return \Dedoc\Scramble\Support\Generator\Types\ObjectType
     */
    public static function createObjectTypeFromCollection(Collection $collection, array $requiredKeys = []): ObjectType
    {
        return self::createObjectTypeFromArray($collection->toArray(), $requiredKeys);
    }
}
