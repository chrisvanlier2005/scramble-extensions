<?php

namespace Lier\ScrambleExtensions\Pagination;

use Dedoc\Scramble\Extensions\TypeToSchemaExtension;
use Dedoc\Scramble\Infer;
use Dedoc\Scramble\OpenApiContext;
use Dedoc\Scramble\Support\Generator\ClassBasedReference;
use Dedoc\Scramble\Support\Generator\Components;
use Dedoc\Scramble\Support\Generator\Reference;
use Dedoc\Scramble\Support\Generator\Types\ArrayType;
use Dedoc\Scramble\Support\Generator\Types\BooleanType;
use Dedoc\Scramble\Support\Generator\Types\IntegerType;
use Dedoc\Scramble\Support\Generator\Types\ObjectType as OpenApiObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\Generator\TypeTransformer;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Pagination\LengthAwarePaginator;
use Lier\ScrambleExtensions\Support\OpenApiObjectHelper;

/**
 * @todo Update
 */
class LengthAwarePaginatorResourceCollectionSchema extends TypeToSchemaExtension
{
    /**
     * Create a new extension instance.
     *
     * @param \Dedoc\Scramble\Infer $infer
     * @param \Dedoc\Scramble\Support\Generator\TypeTransformer $openApiTransformer
     * @param \Dedoc\Scramble\Support\Generator\Components $components
     * @param \Dedoc\Scramble\OpenApiContext $openApiContext
     * @return void
     */
    public function __construct(
        Infer $infer,
        TypeTransformer $openApiTransformer,
        Components $components,
        protected OpenApiContext $openApiContext,
    ) {
        parent::__construct($infer, $openApiTransformer, $components);
    }

    /**
     * Determine whether this extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return bool
     */
    public function shouldHandle(Type $type): bool
    {
        return $type instanceof Generic
            && $type->name === LengthAwarePaginator::class
            && $type->templateTypes === [];
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return mixed
     */
    public function toSchema(Type $type): mixed
    {
        $links = new ArrayType();
        $links->setItems(
            (new OpenApiObjectType())
                ->addProperty('url', (new StringType())->nullable(true))
                ->addProperty('label', new StringType())
                ->addProperty('active', (new BooleanType())->nullable(true))
                ->setRequired([
                    'url',
                    'label',
                    'active',
                ]),
        );

        $meta = OpenApiObjectHelper::createObjectTypeFromArray([
            'current_page' => new IntegerType(),
            'from' => new IntegerType(),
            'last_page' => new IntegerType(),
            'links' => $links,
            'path' => new StringType(),
            'per_page' => new IntegerType(),
            'to' => new IntegerType(),
            'total' => new IntegerType(),
        ])->setRequired([
            'current_page',
            'from',
            'last_page',
            'path',
            'per_page',
            'to',
            'total',
        ]);

        $links = OpenApiObjectHelper::createObjectTypeFromArray([
            'first' => new StringType(),
            'last' => new StringType(),
            'prev' => new StringType(),
            'next' => new StringType(),
        ]);

        return OpenApiObjectHelper::createObjectTypeFromArray([
            'meta' => $meta,
            'links' => $links,
        ])->setRequired([
            'meta',
            'links',
        ]);
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return \Dedoc\Scramble\Support\Generator\Reference
     */
    public function reference(ObjectType $type): Reference
    {
        return ClassBasedReference::create('schemas', $type->name, $this->components);
    }
}
