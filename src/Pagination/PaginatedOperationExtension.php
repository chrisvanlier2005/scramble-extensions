<?php

namespace Lier\ScrambleExtensions\Pagination;

use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Support\Generator\Combined\AllOf;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Parameter;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use Dedoc\Scramble\Support\RouteInfo;
use Dedoc\Scramble\Support\Type\Generic;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

        $this->addPaginationParameters($paginationAttribute->class, $operation);

        $paginator = new Generic(
            name: $paginationAttribute->class,
            templateTypes: [],
        );

        $transformedPaginationClass = $this->openApiTransformer->transform($paginator);

        $content->type = new AllOf()->setItems([
            $type,
            $transformedPaginationClass,
        ]);
    }

    /**
     * @param string $class
     * @param \Dedoc\Scramble\Support\Generator\Operation $operation
     * @return void
     */
    private function addPaginationParameters(string $class, Operation $operation): void
    {
        if (is_a($class, LengthAwarePaginator::class, true)) {
            $pageSchema = new Schema();
            $pageSchema->type = new StringType();

            $operation->addParameters([
                new Parameter('page', 'query')
                    ->description('The current page number')
                    ->setSchema($pageSchema)
            ]);
        }
    }
}
