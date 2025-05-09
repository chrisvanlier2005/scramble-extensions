<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\Concerns\InteractsWithTaggedTypes;
use Lier\ScrambleExtensions\Support\Types\TaggedType;
use Webmozart\Assert\Assert;

class AppendableJsonResourceToSchema extends JsonResourceTypeToSchema
{
    use InteractsWithTaggedTypes;

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof Generic
            && $type->isInstanceOf(JsonResource::class)
            && !$type->isInstanceOf(AnonymousResourceCollection::class)
            && $this->collectAppendType($type) !== null;
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Types\Type
     */
    public function toSchema(Type $type): Generator\Types\Type
    {
        Assert::isInstanceOf($type, Generic::class);

        $appendCallType = $this->collectAppendType($type);

        // TODO: copy the toSchema from JsonResourceTypeToSchema
        $newType = clone $type;
        $newType->templateTypes = new Collection($type->templateTypes)
            ->reject(function (Type $t) {
                return $t instanceof TaggedType;
            })
            ->toArray();

        $schema = $this->openApiTransformer->transform($newType);

        if ($appendCallType === null) {
            return $schema->setDescription(self::class);
        }

        $transformed = $this->openApiTransformer->transform($appendCallType->toKeyedArrayType());

        return new AllOf()->setDescription(self::class)->setItems([
            $schema,
            $transformed,
        ]);
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Response
     */
    public function toResponse(Type $type): Response
    {
        Assert::isInstanceOf($type, Generic::class);

        $cloned = clone $type;
        $cloned->templateTypes = new Collection($type->templateTypes)
            ->reject(fn (Type $t) => $t instanceof TaggedType)
            ->toArray();

        $resourceType = $this->openApiTransformer->transform($cloned);

        $appends = $this->collectAppendType($type);

        if ($appends !== null) {
            // We must wrap it in a `KeyedArrayType` so that
            // the type transformer can infer `?? new MissingValue()`
            $transformed = $this->openApiTransformer->transform($appends->toKeyedArrayType());

            $resourceType = new AllOf()->setItems([$resourceType, $transformed]);
        }

        $resourceType = new Generator\Types\ObjectType()
            ->addProperty('data', $resourceType)
            ->setRequired(['data'])
            ->setDescription(self::class);

        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($resourceType),
        );
    }

    /**
     * Convert the type to a reference.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return null
     */
    public function reference(ObjectType $type): null
    {
        return null;
    }
}
