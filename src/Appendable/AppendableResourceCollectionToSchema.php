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
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\FlattensMergeValues;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\MergesOpenApiObjects;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Resources\MergeValue;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;
use Webmozart\Assert\Assert;

/**
 * @todo Refactor and reformat code. For now experimental.
 */
class AppendableResourceCollectionToSchema extends TypeToSchemaExtension
{
    use FlattensMergeValues;
    use MergesOpenApiObjects;

    /**
     * Determine whether the extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
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
            $typesToAppend = Collection::make($type->templateTypes)
                ->filter(fn (Type $type) => $type instanceof FunctionType)
                ->filter(fn (FunctionType $ft) => $ft->returnType instanceof KeyedArrayType)
                ->flatMap(function (FunctionType $ft) {
                    /** @var \Dedoc\Scramble\Support\Type\KeyedArrayType $returnType */
                    $returnType = $ft->returnType;

                    return Collection::make($returnType->items)
                        ->flatMap(function (ArrayItemType_ $item) {
                            if ($item->isInstanceOf(MergeValue::class)) {
                                return $this->unpackMergeKeyedArrayValue($item->value) ?? [];
                            }

                            return [$item];
                        })
                        ->mapWithKeys(fn (ArrayItemType_ $item) => [
                            (string) $item->key => $this->openApiTransformer->transform($item),
                        ]);
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

        $appendEach = $type->templateTypes[1] ?? null;
        $additional = $type->templateTypes[2] ?? new UnknownType();

        if (!$appendEach instanceof FunctionType || !$appendEach->returnType instanceof KeyedArrayType) {
            return null;
        }

        $definition = $this->infer->analyzeClass($type->name);
        $collecting = new ResourceCollectionTypeInfer()->getBasicCollectionType($definition);

        if ($collecting instanceof \Dedoc\Scramble\Support\Type\UnknownType) {
            return null;
        }

        $collecting = $collecting->value;

        /** @var \Illuminate\Support\Collection<string, mixed> $mappedValuesToAppend */
        $mappedValuesToAppend = Collection::make($appendEach->returnType->items)
            ->flatMap(function (ArrayItemType_ $item) {
                if ($item->value->isInstanceOf(MergeValue::class)) {
                    return $this->unpackMergeKeyedArrayValue($item->value) ?? [];
                }

                return [$item];
            })
            ->mapWithKeys(function (ArrayItemType_ $item) {
                return [
                    (string) $item->key => $this->openApiTransformer->transform($item),
                ];
            });

        $resource = $this->openApiTransformer->transform($collecting);

        if ($mappedValuesToAppend->isEmpty()) {
            return $resource;
        }

        $objectAppends = OpenApiObjectHelper::createObjectTypeFromArray(
            $mappedValuesToAppend->toArray(),
        );

        $openApiType = new AllOf()->setItems([
            $resource,
            $objectAppends,
        ]);

        $openApiType = OpenApiObjectHelper::createObjectTypeFromArray([
            'data' => new OpenApiArrayType()->setItems($openApiType),
        ], required: ['data']);

        $withArray = $definition->getMethodCallType('with');

        if ($withArray instanceof KeyedArrayType) {
            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($withArray));
        }

        if ($additional instanceof KeyedArrayType) {
            $additional->items = $this->flattenMergeValues($additional->items);

            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($additional));
        }

        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($openApiType),
        );
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $value
     * @return list<\Dedoc\Scramble\Support\Type\ArrayItemType_>|null
     */
    private function unpackMergeKeyedArrayValue(Type $value): ?array
    {
        if (!$value instanceof Generic || !$value->isInstanceOf(MergeValue::class)) {
            return null;
        }

        $items = $value->templateTypes[1] ?? null;

        if (!$items instanceof KeyedArrayType) {
            return null;
        }

        return $items->items;
    }
}
