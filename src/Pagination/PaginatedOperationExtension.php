<?php

namespace Lier\ScrambleExtensions\Pagination;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\Generic;
use Illuminate\Support\Collection;
use ReflectionAttribute;

class PaginatedOperationExtension extends OperationExtension
{
    /**
     * Handle the operation extension.
     *
     * @param \Dedoc\Scramble\Support\Generator\Operation $operation
     * @param \Dedoc\Scramble\Support\RouteInfo $routeInfo
     * @return void
     */
    public function handle(Operation $operation, RouteInfo $routeInfo): void
    {
        /** @var \Lier\ScrambleExtensions\Pagination\Paginated|null $paginationAttribute */
        $paginationAttribute = Collection::make($routeInfo->reflectionMethod()?->getAttributes() ?? [])
            ->where(fn (ReflectionAttribute $attribute) => is_a($attribute->getName(), Paginated::class, true))
            ->map(fn (ReflectionAttribute $attribute) => $attribute->newInstance())
            ->first();

        if ($paginationAttribute === null) {
            return;
        }

        /** @var \Dedoc\Scramble\Support\Generator\Response|null $responseToModify */
        $responseToModify = Collection::make($operation->responses ?? [])
            ->whereInstanceOf(Response::class)
            ->where(fn (Response $response) => $response->code === 200)
            ->first();

        $content = $responseToModify?->getContent('application/json');

        $type = $content?->type;

        if (!$type instanceof ObjectType || $content === null) {
            return;
        }

        $paginator = new Generic(
            name: $paginationAttribute->class,
            templateTypes: [],
        );

        $transformedPaginationClass = $this->openApiTransformer->transform($paginator);

        $content->type = (new AllOf())->setItems([
            $type,
            $transformedPaginationClass,
        ]);
    }
}
