<?php

namespace Lier\ScrambleExtensions\Schema\Brick;

use Brick\Money\Money;
use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Webmozart\Assert\Assert;

class MoneyToSchemaExtension extends TypeToSchemaExtension
{
    /**
     * Determine whether this handle should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(Money::class);
    }

    /**
     * Convert the type to an OpenAPI schema object.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Types\ObjectType
     */
    public function toSchema(Type $type): Generator\Types\ObjectType
    {
        Assert::isInstanceOf($type, ObjectType::class);

        $amount = new StringType();
        $amount->description = 'The amount formatted as a string. `<= 999.999.999.999,0000`';
        $amount->examples(['100000.2321']);

        $currency = new StringType();
        $currency->description = 'A 3-letter uppercase ISO 4217 currency code.';
        $currency->examples(['USD']);
        $currency->setMin(3);
        $currency->setMax(3);

        return new Generator\Types\ObjectType()
            ->addProperty('amount', $amount)
            ->addProperty('currency', $currency)
            ->setRequired(['amount', 'currency']);
    }

    /**
     * The reference for the object type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Dedoc\Scramble\Support\Generator\Reference
     */
    public function reference(ObjectType $type): Reference
    {
        $reference = ClassBasedReference::create('schemas', Money::class, $this->components);

        return $reference;
    }
}
