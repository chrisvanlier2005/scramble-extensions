<?php

namespace Lier\ScrambleExtensions\Properties\PhpDoc;

use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\PhpDoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal Mostly copied over from {@see \Dedoc\Scramble\Support\ResponseExtractor\ModelInfo}
 */
class ClassInfo
{
    /**
     * Create a new instance.
     *
     * @param string $class
     * @return void
     */
    public function __construct(
        private string $class,
    ) {
    }

    /**
     * Handle the model information retrieval.
     *
     * @return \Lier\ScrambleExtensions\Properties\PhpDoc\ModelInfo
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function handle(): ModelInfo
    {
        $class = $this->class;

        try {
            $reflectionClass = new ReflectionClass($class);
            $constructor = $reflectionClass->getConstructor();
        } catch (\ReflectionException) {
            return new ModelInfo();
        }

        $attributes = $this->getAttributesFromPhpDoc($reflectionClass->getDocComment());

        if ($constructor !== null) {
            $attributes = $attributes->merge($this->getAttributesFromConstructor($constructor, $reflectionClass));
        }

        return new ModelInfo($attributes);
    }

    /**
     * @param string|false $comment
     * @return \Illuminate\Support\Collection<string, \Lier\ScrambleExtensions\Properties\PhpDoc\PhpDocAttribute>
     */
    private function getAttributesFromPhpDoc(string|false $comment): Collection
    {
        if ($comment === false) {
            return new Collection();
        }

        return new Collection(PhpDoc::parse($comment)->children)
            ->whereInstanceOf(PhpDocTagNode::class)
            ->filter(fn (PhpDocTagNode $tag) => $tag->name === '@property' || $tag->name === '@property-read')
            ->filter(fn (PhpDocTagNode $tag) => $tag->value instanceof PropertyTagValueNode)
            ->mapWithKeys(function (PhpDocTagNode $tag) {
                /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode&object{type: \PHPStan\PhpDocParser\Ast\Type\TypeNode, propertyName: string} $value */
                $value = $tag->value;

                $type = PhpDocTypeHelper::toType($value->type);

                $name = substr($value->propertyName, 1); // Remove the leading '$' character.

                return [
                    $name => new PhpDocAttribute(
                        name: $name,
                        type: $type,
                        isReadOnly: $tag->name === '@property-read',
                        node: $tag,
                    ),
                ];
            });
    }

    private function getAttributesFromConstructor(ReflectionMethod $constructor, ReflectionClass $class): Collection
    {
        $comment = $constructor->getDocComment();

        if ($comment === false) {
            return new Collection();
        }

        return new Collection(PhpDoc::parse($comment)->children)
            ->whereInstanceOf(PhpDocTagNode::class)
            ->filter(fn (PhpDocTagNode $tag) => $tag->name === '@param')
            ->filter(fn (PhpDocTagNode $tag) => $tag->value instanceof ParamTagValueNode)
            ->mapWithKeys(function (PhpDocTagNode $tag) use ($class) {
                /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode $value */
                $value = $tag->value;

                $name = substr($value->parameterName, 1); // Remove the leading '$' character.

                try {
                    // Ensure that it's a promoted property.
                    $_ = $class->getProperty($name);
                } catch (\ReflectionException) {
                    return [];
                }

                $type = PhpDocTypeHelper::toType($value->type);

                return [
                    $name => new PhpDocAttribute(
                        name: $name,
                        type: $type,
                        isReadOnly: false,
                        node: $tag,
                    ),
                ];
            });
    }
}
