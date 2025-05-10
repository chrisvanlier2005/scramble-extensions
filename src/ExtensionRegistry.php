<?php

namespace Lier\ScrambleExtensions;

use Dedoc\Scramble\Support\InferExtensions\JsonResourceExtension;
use Lier\ScrambleExtensions\Appendable\AppendableAnonymousJsonResourceCollectionSchema;
use Lier\ScrambleExtensions\Appendable\AppendableJsonResourceToSchema;
use Lier\ScrambleExtensions\Appendable\AppendableResourceCollectionToSchema;
use Lier\ScrambleExtensions\Appendable\AppendMethodCallExtension;
use Lier\ScrambleExtensions\Appendable\AppendsEachMethodCallExtension;
use Lier\ScrambleExtensions\Pagination\LengthAwarePaginatorSchemaExtension;
use Lier\ScrambleExtensions\Pagination\PaginatedOperationExtension;
use Lier\ScrambleExtensions\Properties\JsonResourceWithObject;
use Lier\ScrambleExtensions\Properties\PhpDoc\PropertyTypesFromPhpDocExtension;
use Lier\ScrambleExtensions\Resources\MergeWhenCallExtension;
use Lier\ScrambleExtensions\Schema\Brick\BigDecimalToSchemaExtension;
use Lier\ScrambleExtensions\Schema\Brick\CurrencyToSchemaExtension;
use Lier\ScrambleExtensions\Schema\Brick\MoneyToSchemaExtension;
use Lier\ScrambleExtensions\Schema\Propaganistas\PhoneToSchemaExtension;
use Lier\ScrambleExtensions\Validation\CurrencyRuleExtension;
use Lier\ScrambleExtensions\Validation\MoneyRuleExtension;

class ExtensionRegistry
{
    /**
     * @var list<class-string>
     */
    public const array TYPE_TO_SCHEMA = [
        PhoneToSchemaExtension::class,
        MoneyToSchemaExtension::class,
        BigDecimalToSchemaExtension::class,
        CurrencyToSchemaExtension::class,
    ];

    /**
     * Only include these extensions with the modified variant of scramble.
     * Due to missing static analysis in the original package.
     *
     * @var list<class-string>
     */
    public const array VALIDATION_RULES = [
        CurrencyRuleExtension::class,
        MoneyRuleExtension::class,
    ];

    /**
     * @var list<class-string>
     */
    public const array APPENDABLE_RESOURCES = [
        AppendableAnonymousJsonResourceCollectionSchema::class,
        AppendableJsonResourceToSchema::class,
        AppendMethodCallExtension::class,
        AppendsEachMethodCallExtension::class,
        AppendableResourceCollectionToSchema::class,

        // TODO: make it's own category? Not directly related to appendable resources, but
        // its used in the same context.
        MergeWhenCallExtension::class,
    ];

    /**
     * @var list<class-string>
     */
    public const array PAGINATION = [
        // TODO: extend with more pagination types. e.g. `SimplePaginator` & `CursorPaginator`
        LengthAwarePaginatorSchemaExtension::class,
        PaginatedOperationExtension::class,
    ];

    /**
     * @var list<class-string>
     */
    public const array PROPERTIES = [
        JsonResourceWithObject::class,
        PropertyTypesFromPhpDocExtension::class,
    ];
}
