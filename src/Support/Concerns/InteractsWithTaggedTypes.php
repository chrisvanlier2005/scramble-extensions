<?php

namespace Lier\ScrambleExtensions\Support\Concerns;

use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\TypeToSchemaExtensions\FlattensMergeValues;
use Illuminate\Support\Collection;
use Lier\ScrambleExtensions\Support\Types\TaggedFunctionType;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;

trait InteractsWithTaggedTypes
{
    use FlattensMergeValues;

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Illuminate\Support\Collection<int, \Dedoc\Scramble\Support\Type\Type>
     */
    private function collectAppendEachTypes(Generic $type): Collection
    {
        $items = new Collection($type->templateTypes)
            ->where(function (Type $type) {
                // In this case we only support tagged function types.
                return $type instanceof TaggedFunctionType
                    && $type->tag === 'appendEach';
            })
            ->filter(fn (TaggedFunctionType $ft) => $ft->returnType instanceof KeyedArrayType)
            ->map(fn (TaggedFunctionType $ft) => $ft->returnType)
            ->flatMap(fn (KeyedArrayType $returnType) => $returnType->items);

        $flattened = $this->flattenMergeValues($items->toArray());

        return new Collection($flattened);
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Generic $type
     * @return \Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType|null
     */
    private function collectAdditionalType(Generic $type): ?TaggedKeyedArrayType
    {
        return new Collection($type->templateTypes)
            ->where(function (Type $type) {
                // In this case we only support tagged function types.
                return $type instanceof TaggedKeyedArrayType
                    && $type->tag === 'additional';
            })
            ->first();
    }
}