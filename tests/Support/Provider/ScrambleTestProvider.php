<?php

namespace Tests\Support\Provider;

use Dedoc\Scramble\Configuration\OperationTransformers;
use Dedoc\Scramble\Extensions\OperationExtension;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\ApiKeySecurityScheme;
use Illuminate\Support\ServiceProvider;
use Lier\ScrambleExtensions\ExtensionRegistry;

class ScrambleTestProvider extends ServiceProvider
{
    public function boot(): void
    {
        Scramble::$extensions = [
            ...ExtensionRegistry::PAGINATION,
            ...ExtensionRegistry::PHPDOC_PROPERTIES,
            ...ExtensionRegistry::APPENDABLE_RESOURCES,
            ...ExtensionRegistry::VALIDATION_RULES,
            ...ExtensionRegistry::TYPE_TO_SCHEMA,
        ];

        Scramble::throwOnError();
        Scramble::configure()
            ->withOperationTransformers(function (OperationTransformers $transformers) {
                $operationExtensions = array_values(array_filter(
                    Scramble::$extensions,
                    fn ($e) => is_a($e, OperationExtension::class, true),
                ));

                $transformers->append($operationExtensions);
            })
            ->withDocumentTransformers(function (OpenApi $document) {
                $document->secure(
                    new ApiKeySecurityScheme('cookie', 'laravel-session')
                        ->setDescription('The authenticated session cookie')
                        ->as('laravel-session'),
                );
                $document->secure(
                    new ApiKeySecurityScheme('header', 'X-CSRF-TOKEN')
                        ->setDescription(
                            'A header containing the current CSRF token',
                        )
                        ->as('csrf_token'),
                );
            });
    }
}