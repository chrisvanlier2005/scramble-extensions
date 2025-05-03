<?php

namespace Lier\ScrambleExtensions\Support;

use Dedoc\Scramble\Support\Generator\Types\ObjectType;

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
}
