<?php

namespace Lier\ScrambleExtensions\Support\Types;

use Dedoc\Scramble\Support\Type\AbstractType;
use Dedoc\Scramble\Support\Type\ArrayItemType_;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Illuminate\Support\Collection;

/**
 * Does not extend KeyedArrayType due to some strange internal issues.
 * The TaggedKeyedArray will be transformed somewhere in Scramble.
 *
 * @internal
 */
final class TaggedKeyedArrayType extends AbstractType implements TaggedType
{
    public bool $isList = false;

    /**
     * @param array<\Dedoc\Scramble\Support\Type\ArrayItemType_> $items
     */
    public function __construct(
        public readonly string $tag,
        public array $items = [],
        ?bool $isList = null
    ) {
        if ($isList === null) {
            $this->isList = TaggedKeyedArrayType::checkIsList($items);
        } else {
            $this->isList = $isList;
        }
    }

    public function toKeyedArrayType(): KeyedArrayType
    {
        return new KeyedArrayType(
            items: $this->items,
            isList: $this->isList,
        );
    }

    /**
     * @param array<\Dedoc\Scramble\Support\Type\ArrayItemType_> $items
     * @return bool
     */
    public static function checkIsList(array $items): bool
    {
        return new Collection($items)->every(fn (ArrayItemType_ $item) => $item->key === null)
            || new Collection($items)->every(fn (ArrayItemType_ $item) => is_numeric($item->key)); // @todo add consecutive check to be sure it is really a list
    }

    /**
     * @return list<string>
     */
    public function nodes(): array
    {
        return ['items'];
    }

    /**
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @return false
     */
    public function isSame(Type $type): false
    {
        return false;
    }

    /**
     * @return string
     */
    public function toString(): string
    {
        $name = $this->isList ? 'list' : 'array';

        $numIndex = 0;

        return sprintf(
            '%s{%s}',
            $name,
            implode(', ', array_map(function (ArrayItemType_ $item) use (&$numIndex) {
                $str = $this->isList ? sprintf(
                    '%s',
                    $item->value->toString()
                ) : sprintf(
                    '%s%s: %s',
                    $item->isNumericKey() ? $numIndex : $item->key,
                    $item->isOptional ? '?' : '',
                    $item->value->toString()
                );

                if ($item->isNumericKey()) {
                    $numIndex++;
                }

                return $str;
            }, $this->items))
        );
    }
}