<?php

namespace Lier\ScrambleExtensions\Properties\PhpDoc;

use Dedoc\Scramble\Support\Type\Type;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;

final readonly class PhpDocAttribute
{
    /**
     * @param string $name
     * @param \Dedoc\Scramble\Support\Type\Type $type
     * @param bool $isReadOnly
     * @param \PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode $node
     */
    public function __construct(
        public string $name,
        public Type $type,
        public bool $isReadOnly,
        public PhpDocTagNode $node,
    ) {
    }
}
