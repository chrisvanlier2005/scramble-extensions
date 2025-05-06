<?php

namespace Lier\ScrambleExtensions\Appendable;

use Dedoc\Scramble\Infer\Extensions\ExpressionTypeInferExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Support\Type\FunctionType;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\KeyedArrayType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\TypeHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Lier\ScrambleExtensions\Support\Types\TaggedFunctionType;
use Lier\ScrambleExtensions\Support\Types\TaggedKeyedArrayType;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use UnexpectedValueException;

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

            $functionCall = TypeHelper::getArgType($scope, $node->args, ['callback', 0]);

            if (!$functionCall instanceof FunctionType) {
                throw new UnexpectedValueException(sprintf(
                    'Invalid type, expected %s, got %s',
                    FunctionType::class,
                    get_debug_type($type)
                ));
            }

            $type->templateTypes = array_merge($type->templateTypes, [
                new TaggedFunctionType(
                    'appendEach',
                    $functionCall->name,
                    $functionCall->arguments,
                    $functionCall->returnType,
                )
            ]);

            return $type;
        }

        return null;
    }
}
