<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

class InferAppendableAnonymousResourceCollection implements ExpressionTypeInferExtension
{
    /**
     * @param \PhpParser\Node\Expr $node
     * @param \Dedoc\Scramble\Infer\Scope\Scope $scope
     * @return \Dedoc\Scramble\Support\Type\Type|null
     */
    public function getType(Expr $node, Scope $scope): ?Type
    {
        if (!$node instanceof Expr\MethodCall || !$node->name instanceof Identifier) {
            return null;
        }

        if (!$scope->getType($node->var)->isInstanceOf(JsonResource::class)) {
            return null;
        }

        if ($node->name->toString() === 'appendEach' && isset($node->args[0])) {
            $type = $scope->getType($node->var);

            if (!$type instanceof Generic) {
                return null;
            }

            $type->templateTypes = array_merge($type->templateTypes, [
                TypeHelper::getArgType($scope, $node->args, ['callback', 0]),
            ]);

            return $type;
        }

        return null;
    }
}
