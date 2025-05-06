<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\JsonResourceTypeToSchema;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\Concerns\InteractsWithTaggedTypes;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;
use Webmozart\Assert\Assert;

/**
 * @todo refactor.
 */
class AppendableJsonResourceToSchema extends JsonResourceTypeToSchema
{
    use InteractsWithTaggedTypes;

    public static string $jsonResourceName = 'App\Http\Resources\JsonResource';
    public static string $anonymousResourceCollectionName = 'App\Http\Resources\AnonymousResourceCollection';

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof Generic
            && $type->isInstanceOf(self::$jsonResourceName)
            && !$type->isInstanceOf(self::$anonymousResourceCollectionName)
            && count($type->templateTypes) === 2;
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return mixed
     */
    public function toSchema(Type $type): mixed
    {
        $appendableTypes = Collection::make($type->templateTypes)
            ->filter(fn (Type $type) => $type instanceof KeyedArrayType)
            ->flatMap(function (KeyedArrayType $keyedArrayType) {
                return Collection::make($keyedArrayType->items)
                    ->mapWithKeys(fn (ArrayItemType_ $item) => [
                        (string)$item->key => $this->openApiTransformer->transform($item),
                    ]);
            });

        $newType = clone $type;

        $newType->templateTypes = Collection::make($type->templateTypes)
            ->filter(fn (mixed $templateType) => !$templateType instanceof KeyedArrayType)
            ->toArray();

        if ($appendableTypes->isEmpty()) {
            return $this->openApiTransformer->transform($newType);
        }

        return new AllOf()->setItems([
            $this->openApiTransformer->transform($newType),
            OpenApiObjectHelper::createObjectTypeFromArray($appendableTypes->toArray()),
        ]);
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Dedoc\Scramble\Support\Generator\Response
     */
    public function toResponse(Type $type): Response
    {
        Assert::isInstanceOf($type, Generic::class);

        $newType = clone $type;

        $newType->templateTypes = Collection::make($type->templateTypes)
            ->filter(fn (mixed $templateType) => !$templateType instanceof KeyedArrayType)
            ->toArray();

        $openApiType = $this->openApiTransformer->transform($newType);

        $appends = Collection::make($type->templateTypes)
            ->whereInstanceOf(KeyedArrayType::class)
            ->flatMap(function (KeyedArrayType $keyedArrayType) {
                return $keyedArrayType->items;
            })
            ->mapWithKeys(fn(ArrayItemType_ $item) => [
                (string) $item->key => $this->openApiTransformer->transform($item),
            ]);

        if ($appends->isNotEmpty()) {
            $openApiType = (new AllOf())
                ->setItems([
                    $openApiType,
                    OpenApiObjectHelper::createObjectTypeFromArray($appends->toArray()),
                ]);
        }

        $openApiType = (new OpenApiObjectType())
            ->addProperty('data', $openApiType)
            ->setRequired(['data']);

        return Response::make(200)->setContent(
            'application/json',
            Schema::fromType($openApiType),
        );
    }

    public function reference(ObjectType $type)
    {
        return null;
    }
}
