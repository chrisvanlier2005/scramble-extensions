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
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;

/**
 * @todo Refactor and reformat code. For now experimental.
 */
class AppendableResourceCollectionToSchema extends TypeToSchemaExtension
{
    use FlattensMergeValues;
    use MergesOpenApiObjects;

    public function shouldHandle(Type $type): bool
    {
        return $type instanceof ObjectType
            && $type->isInstanceOf(ResourceCollection::class);
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Types\ArrayType|\Dedoc\Scramble\Support\Generator\Types\UnknownType|null
     */
    public function toSchema(Type $type): OpenApiArrayType|UnknownType|null
    {
        $definition = $this->infer->analyzeClass($type->name);

        $array = (new ResourceCollectionTypeInfer)->getBasicCollectionType($definition);

        $typesToAppend = [];

        if ($type instanceof Generic) {
            $typesToAppend = Collection::make($type->templateTypes)
                ->filter(fn (Type $type) => $type instanceof FunctionType)
                ->filter(fn (FunctionType $ft) => $ft->returnType instanceof KeyedArrayType)
                ->flatMap(function (FunctionType $ft) {
                    /** @var \Dedoc\Scramble\Support\Type\KeyedArrayType $returnType */
                    $returnType = $ft->returnType;

                    return Collection::make($returnType->items)
                        ->mapWithKeys(fn (ArrayItemType_ $item) => [
                            (string) $item->key => $this->openApiTransformer->transform($item),
                        ]);
                });
        }

        if ($array instanceof \Dedoc\Scramble\Support\Type\UnknownType) {
            return null;
        }

        $resourceOpenApiType = $this->openApiTransformer->transform(
            $array->value,
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
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return \Dedoc\Scramble\Support\Generator\Response|null
     */
    public function toResponse(Type $type)
    {
        $appendEach = $type->templateTypes[1] ?? null;
        $additional = $type->templateTypes[2 /* TAdditional */] ?? new UnknownType;

        if (!$appendEach instanceof FunctionType || !$appendEach->returnType instanceof KeyedArrayType) {
            return null;
        }

        $definition = $this->infer->analyzeClass($type->name);
        $collecting = (new ResourceCollectionTypeInfer)->getBasicCollectionType($definition);

        if ($collecting instanceof \Dedoc\Scramble\Support\Type\UnknownType) {
            return null;
        }

        $collecting = $collecting->value;

        /** @var \Illuminate\Support\Collection<string, mixed> $mappedValuesToAppend */
        $mappedValuesToAppend = Collection::make($appendEach->returnType->items)
            ->mapWithKeys(function (ArrayItemType_ $item) {
                return [
                    (string) $item->key => $this->openApiTransformer->transform($item),
                ];
            });

        if ($mappedValuesToAppend->isEmpty()) {
            return null;
        }

        $objectAppends = OpenApiObjectHelper::createObjectTypeFromArray(
            $mappedValuesToAppend->toArray(),
        );

        $jsonResourceOpenApiType = $this->openApiTransformer->transform($collecting);

        $openApiType = (new AllOf())
            ->setItems([
                $jsonResourceOpenApiType,
                $objectAppends,
            ]);

        $openApiType = new OpenApiArrayType()
            ->setItems($openApiType);

        $withArray = $definition->getMethodCallType('with');

        $openApiType = OpenApiObjectHelper::createObjectTypeFromArray([
            'data' => $openApiType,
        ], ['data']);

        if ($withArray instanceof KeyedArrayType) {
            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($withArray));
        }

        if ($additional instanceof KeyedArrayType) {
            $additional->items = $this->flattenMergeValues($additional->items);
            $this->mergeOpenApiObjects($openApiType, $this->openApiTransformer->transform($additional));;
        }

        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($openApiType),
        );
    }
}