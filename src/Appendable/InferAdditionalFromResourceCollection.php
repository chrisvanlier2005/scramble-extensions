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
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

class InferAdditionalFromResourceCollection implements ExpressionTypeInferExtension
{

    public function getType(Expr $node, Scope $scope): ?Type
    {
        if (!$node instanceof Expr\MethodCall || !$node->name instanceof Identifier) {
            return null;
        }

        if (!$scope->getType($node->var)->isInstanceOf(JsonResource::class)) {
            return null;
        }

        if ($node->name->toString() === 'additional' && isset($node->args[0])) {
            $type = $scope->getType($node->var);

            if (!$type instanceof Generic) {
                return null;
            }

            $argument = TypeHelper::getArgType($scope, $node->args, ['data', 0]);

            if (!$argument instanceof KeyedArrayType) {
                return null;
            }

            $type->templateTypes = [
                ...$type->templateTypes,
                new TaggedKeyedArrayType(
                    tag: 'additional',
                    items: $argument->items,
                    isList: $argument->isList,
                ),
            ];

            return $type;
        }

        return null;
    }
}