<?php

namespace Lier\ScrambleExtensions\Support\Types;

use Dedoc\Scramble\Support\Type\FunctionType;

/**
 * @internal
 */
final class TaggedFunctionType extends FunctionType implements TaggedType
{
    public function __construct(
        public string $tag,
        string $name,
        $arguments = [],
        $returnType = null,
        $exceptions = [],
    ) {
        parent::__construct($name, $arguments, $returnType, $exceptions);
    }
}