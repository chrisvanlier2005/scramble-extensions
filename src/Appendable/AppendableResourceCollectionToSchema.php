<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType as OpenApiArrayType;
use Dedoc\Scramble\Support\Generator\Types\UnknownType;
use Dedoc\Scramble\Support\InferExtensions\ResourceCollectionTypeInfer;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\FlattensMergeValues;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\MergesOpenApiObjects;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\Concerns\InteractsWithTaggedTypes;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;

/**
 * @todo Refactor and reformat code. For now experimental.
 */
class AppendableResourceCollectionToSchema extends TypeToSchemaExtension
{
    use FlattensMergeValues;
    use MergesOpenApiObjects;
    use InteractsWithTaggedTypes;

    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return ($type instanceof ObjectType)
            && $type->isInstanceOf(ResourceCollection::class);
    }

    /**
     * Convert the given type to an OpenAPI schema.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Types\ArrayType|\Dedoc\Scramble\Support\Generator\Types\UnknownType|null
     */
    public function toSchema(Type $type): OpenApiArrayType|UnknownType|null
    {
        $definition = $this->infer->analyzeClass($type->name);

        $collectionType = new ResourceCollectionTypeInfer()->getBasicCollectionType($definition);

        $typesToAppend = new Collection();

        if ($type instanceof Generic) {
            $appendEachCallParameters = $this->collectAppendEachTypes($type);

            // TODO: additional?
            $typesToAppend = $appendEachCallParameters->mapWithKeys(function (ArrayItemType_ $item) {
                $key = $item->key ?? 'unknown';

                return [(string) $key => $this->openApiTransformer->transform($item)];
            });
        }

        if ($collectionType instanceof \Dedoc\Scramble\Support\Type\UnknownType) {
            return null;
        }

        $resourceOpenApiType = $this->openApiTransformer->transform(
            $collectionType->value,
        );

        if ($typesToAppend->isEmpty()) {
            return new OpenApiArrayType()->setItems($resourceOpenApiType);
        }

        return new OpenApiArrayType()->setItems(
            new AllOf()->setItems([
                $resourceOpenApiType,
                OpenApiObjectHelper::createObjectTypeFromArray($typesToAppend->toArray()),
            ]),
        );
    }

    /**
     * Convert the given type to an OpenAPI response.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Response|null
     */
    public function toResponse(Type $type): ?Response
    {
        if (!$type instanceof Generic) {
            return null; // Can be handled by a different extension.
        }

        $appendEachCallParameters = $this->collectAppendEachTypes($type);

        $definition = $this->infer->analyzeClass($type->name);
        $collecting = new ResourceCollectionTypeInfer()->getBasicCollectionType($definition);

        if ($collecting instanceof \Dedoc\Scramble\Support\Type\UnknownType) {
            // Unable to determine the collection type.
            return null;
        }

        $collecting = $collecting->value;

        $transformed = $appendEachCallParameters->mapWithKeys(function (ArrayItemType_ $item) {
            $key = $item->key ?? 'unknown';

            return [(string) $key => $this->openApiTransformer->transform($item)];
        });

        $openApiType = $this->openApiTransformer->transform($collecting);

        if ($transformed->isNotEmpty()) {
            $openApiType = new AllOf()->setItems([
                $openApiType,
                OpenApiObjectHelper::createObjectTypeFromCollection($transformed),
            ]);
        }

        $openApiType = OpenApiObjectHelper::createObjectTypeFromArray([
            'data' => new OpenApiArrayType()->setItems($openApiType),
        ], required: ['data']);

        $withArray = $definition->getMethodCallType('with');

        if ($withArray instanceof KeyedArrayType) {
            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($withArray));
        }

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
}
