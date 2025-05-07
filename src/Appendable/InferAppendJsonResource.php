<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;
use PhpParser\Node;
use PhpParser\Node\Expr;
use UnexpectedValueException;
use Webmozart\Assert\Assert;

class InferAppendJsonResource implements ExpressionTypeInferExtension
{
    /**
     * @param \PhpParser\Node\Expr $node
     * @param \Dedoc\Scramble\Infer\Scope\Scope $scope
     * @return \Dedoc\Scramble\Support\Type\Type|null
     */
    public function getType(Expr $node, Scope $scope): ?Type
    {
        if (
            !$node instanceof Node\Expr\MethodCall
            || !$node->name instanceof Node\Identifier
        ) {
            return null;
        }

        if (!$scope->getType($node->var)->isInstanceOf(JsonResource::class)) {
            return null;
        }

        if ($node->name->toString() === 'append' && isset($node->args[0])) {
            $type = $scope->getType($node->var);

            if (!$type instanceof Generic) {
                return null;
            }

            Assert::allIsInstanceOf($node->args, Node\Arg::class);

            $keyedArrayType = TypeHelper::getArgType($scope, $node->args, ['additional', 0]);

            if (!$keyedArrayType instanceof KeyedArrayType) {
                throw new UnexpectedValueException(sprintf(
                    'Invalid type, expected %s, got %s',
                    KeyedArrayType::class,
                    get_debug_type($type)
                ));
            }

            $type->templateTypes = array_merge($type->templateTypes, [
                new TaggedKeyedArrayType(
                    tag: 'append',
                    items: $keyedArrayType->items,
                    isList: $keyedArrayType->isList,
                )
            ]);

            return $type;
        }

        return null;
    }
}
