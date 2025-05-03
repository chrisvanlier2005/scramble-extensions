<?php

namespace Lier\ScrambleExtensions\Schema\Propaganistas;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Propaganistas\LaravelPhone\PhoneNumber;

final class PhoneToSchemaExtension extends TypeToSchemaExtension
{
    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(PhoneNumber::class);
    }

    /**
     * Convert the type to an OpenAPI schema object.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Types\StringType
     */
    public function toSchema(Type $type): StringType
    {
        return new StringType()
            ->setDescription('Phone number in E.164 format.')
            ->example('+31612345678');
    }

    /**
     * The reference for the object type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Dedoc\Scramble\Support\Generator\Reference
     */
    public function reference(ObjectType $type): Reference
    {
        return ClassBasedReference::create('schemas', $type->name, $this->components);
    }
}
