<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Support\Generator;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeWalker;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\AnonymousResourceCollectionTypeToSchema;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Lier\ScrambleExtensions\Support\Concerns\InteractsWithTaggedTypes;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;
use Webmozart\Assert\Assert;

class AppendableAnonymousJsonResourceCollectionSchema extends AnonymousResourceCollectionTypeToSchema
{
    use InteractsWithTaggedTypes;

    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof Generic
            && $type->isInstanceOf(AnonymousResourceCollection::class);
    }

    /**
     * Convert the given type to an OpenAPI schema.
     *
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Types\ArrayType
     */
    public function toSchema(Type $type): ?Generator\Types\ArrayType
    {
        Assert::isInstanceOf($type, Generic::class);

        $collectingResourceType = $this->getCollectingResourceType($type);

        if ($collectingResourceType === null) {
            return null;
        }

        $appendEachCallParameters = $this->collectAppendEachTypes($type);

        $resourceSchema = $this->openApiTransformer->transform($collectingResourceType);

        if ($appendEachCallParameters->isEmpty()) {
            return new Generator\Types\ArrayType()->setItems($resourceSchema);
        }

        $transformed = $this->openApiTransformer->transform(
            // We must wrap it in a `KeyedArrayType` so that
            // the type transformer can infer `?? new MissingValue()`
            new KeyedArrayType($appendEachCallParameters->toArray()),
        );

        return new Generator\Types\ArrayType()->setItems(
            new AllOf()->setItems([$resourceSchema, $transformed]),
        );
    }

    /**
     * Convert the given type to an OpenAPI response.
     *
     * Is called when converting an anonymous resource to an OpenAPI response.
     * E.g. `UserResource::collection($users)->appendEach(...)->additional(...)`
     *
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Response|null
     */
    public function toResponse(Type $type): ?Response
    {
        Assert::isInstanceOf($type, Generic::class);

        $collectingResourceType = $this->getCollectingResourceType($type);
        $appendEachParameters = $this->collectAppendEachTypes($type);

        $openApiType = $this->openApiTransformer->transform($collectingResourceType);

        if ($appendEachParameters->isNotEmpty()) {
            $transformed = $this->openApiTransformer->transform(
                // We must wrap it in a `KeyedArrayType` so that
                // the type transformer can infer `?? new MissingValue()`
                new KeyedArrayType($appendEachParameters->toArray()),
            );

            $openApiType = new AllOf()->setItems([$openApiType, $transformed]);
        }

        $openApiType = OpenApiObjectHelper::createObjectTypeFromArray([
            'data' => new Generator\Types\ArrayType()->setItems($openApiType),
        ], ['data']);

        $additional = $this->collectAdditionalType($type);

        if ($additional instanceof TaggedKeyedArrayType) {
            $additional = $additional->toKeyedArrayType();
            $additional->items = $this->flattenMergeValues($additional->items);

            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($additional));
        }

        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($openApiType),
        );
    }

    /**
     * Get the collecting resource type from the given generic type.
     *
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Type\Type|null
     * @see \Dedoc\Scramble\Support\TypeToSchemaExtensions\AnonymousResourceCollectionTypeToSchema::getCollectingResourceType()
     */
    private function getCollectingResourceType(Generic $type): ?Type
    {
        return new TypeWalker()->first(
            $type->templateTypes[0],
            fn (Type $t) => $t->isInstanceOf(JsonResource::class),
        );
    }
}
