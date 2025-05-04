<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ArrayType as OpenApiArrayType;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeWalker;
use Dedoc\Scramble\Support\Type\Union;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\AnonymousResourceCollectionTypeToSchema;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;

class AppendableJsonResourceCollectionSchema extends AnonymousResourceCollectionTypeToSchema
{
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
     * @return OpenApiArrayType
     */
    public function toSchema(Type $type): OpenApiArrayType
    {
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

        $resourceOpenApiType = $this->openApiTransformer->transform(
            $this->getCollectingResourceType($type),
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
     * Get the collecting resource type from the given generic type.
     *
     * @see AnonymousResourceCollectionTypeToSchema::getCollectingResourceType()
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return mixed
     */
    private function getCollectingResourceType(Generic $type): mixed
    {
        // In the case of paginated resource, we still want to get to the underlying JsonResource.
        return new TypeWalker()->first(
            $type->templateTypes[0],
            fn (Type $t) => $t->isInstanceOf(JsonResource::class),
        );
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Response|null
     * @todo refactor.
     */
    public function toResponse(Type $type): ?Response
    {
        $appendEach = $type->templateTypes[1] ?? null;

        if (!$appendEach instanceof FunctionType || !$appendEach->returnType instanceof KeyedArrayType) {
            return null;
        }

        $collectingResourceType = $this->getCollectingResourceType($type);

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
            $mappedValuesToAppend->keys()->diff($this->getOptionalKeys($appendEach->returnType))->toArray(),
        );

        $jsonResourceOpenApiType = $this->openApiTransformer->transform($collectingResourceType);

        $openApiType = (new AllOf())
            ->setItems([
                $jsonResourceOpenApiType,
                $objectAppends,
            ]);

        $openApiType = new OpenApiArrayType()
            ->setItems($openApiType);

        $openApiType = OpenApiObjectHelper::createObjectTypeFromArray([
            'data' => $openApiType,
        ], ['data']);


        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($openApiType),
        );
    }

    /**
     * Get the keys that are optional in the given keyed array type.
     * Null-coalescing is transformed to a `Union` type. So we can infer the MissingValue type.
     *
     * <code>
     *     UserResource::make($user)->append(fn () => [
     *        'property' => PropertyResource::make($user->property ?? new MissingValue()),
     *     ])
     * </code>
     *
     * @param \Dedoc\Scramble\Support\Type\KeyedArrayType $items
     * @return array<string>
     */
    private function getOptionalKeys(KeyedArrayType $items): array
    {
        $keys = [];

        foreach ($items->items as $item) {
            if (!$item->value instanceof Generic) {
                continue;
            }

            $type = $item->value->templateTypes[0] ?? null;

            if (!$type instanceof Union) {
                continue;
            }

            foreach ($type->types as $unionType) {
                if (!$unionType instanceof \Dedoc\Scramble\Support\Type\ObjectType) {
                    continue;
                }

                if ($unionType->isInstanceOf(MissingValue::class)) {
                    $keys[] = (string) $item->key;
                }
            }
        }

        return $keys;
    }
}
