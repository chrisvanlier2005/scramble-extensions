<?php

namespace Lier\ScrambleExtensions\Schema\Brick;

use Brick\Math\BigDecimal;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Webmozart\Assert\Assert;

class BigDecimalToSchemaExtension extends TypeToSchemaExtension
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
            && $type->isInstanceOf(BigDecimal::class);
    }

    /**
     * Convert the type to an OpenAPI schema object.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Types\StringType
     */
    public function toSchema(Type $type): StringType
    {
        Assert::isInstanceOf($type, ObjectType::class);

        return new StringType()
            ->setDescription('The amount formatted as a string. `<= 999.999.999.999,00`')
            ->example('12456.78');
    }

    /**
     * The reference for the object type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Dedoc\Scramble\Support\Generator\Reference
     */
    public function reference(ObjectType $type): Reference
    {
        return ClassBasedReference::create('schemas', BigDecimal::class, $this->components);
    }
}
