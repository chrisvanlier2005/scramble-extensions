<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use PhpParser\Node;
use PhpParser\Node\Expr;
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

            $type->templateTypes = array_merge($type->templateTypes, [
                TypeHelper::getArgType($scope, $node->args, ['additional', 0]),
            ]);

            return $type;
        }

        return null;
    }
}
