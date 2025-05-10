<?php

namespace Lier\ScrambleExtensions\Properties;

use Dedoc\Scramble\Infer\Definition\ClassDefinition;
use Dedoc\Scramble\Infer\Extensions\Event\PropertyFetchEvent;
use Dedoc\Scramble\Infer\Extensions\PropertyTypeExtension;
use Dedoc\Scramble\Infer\Scope\Scope;
use Dedoc\Scramble\Infer\Services\FileNameResolver;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;
use Dedoc\Scramble\Support\Type\UnknownType;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use ReflectionClass;

class JsonResourceWithObject implements PropertyTypeExtension
{
    /**
     * Copied from JsonResourceHelper.
     *
     * @var array<string, \Dedoc\Scramble\Support\Type\Type>
     */
    public static $cache = [];

    /**
     * Determine whether this extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\ObjectType $type
     * @return bool
     */
    public function shouldHandle(ObjectType $type): bool
    {
        return $type->isInstanceOf(JsonResource::class);
    }

    /**
     * Get the property type for the given property fetch event.
     *
     * @throws \ReflectionException
     */
    public function getPropertyType(PropertyFetchEvent $event): ?Type
    {
        return match ($event->name) {
            'resource' => $this->getTypeFromJsonResource($event->getDefinition(), $event->scope),
            default => null,
        };
    }

    /**
     * Mostly copied from JsonResourceHelper.
     *
     * @param \Dedoc\Scramble\Infer\Definition\ClassDefinition $jsonClass
     * @param \Dedoc\Scramble\Infer\Scope\Scope $scope
     * @return \Dedoc\Scramble\Support\Type\Type|null
     * @throws \ReflectionException
     */
    private function getTypeFromJsonResource(ClassDefinition $jsonClass, Scope $scope): ?Type
    {
        if ($cachedType = static::$cache[$jsonClass->name] ?? null) {
            return $cachedType;
        }

        $modelClass = $this->getClassName(new ReflectionClass($jsonClass->name), $scope->nameResolver);

        $type = new UnknownType();

        if (class_exists($modelClass)) {
            $type = new ObjectType($modelClass);
        }

        static::$cache[$jsonClass->name] = $type;

        return $type;
    }

    private function getClassName(ReflectionClass $reflectionClass, FileNameResolver $getFqName): ?string
    {
        $phpDoc = $reflectionClass->getDocComment();

        if (!$phpDoc) {
            $phpDoc = '';
        }

        $mixinOrPropertyLine = Str::of($phpDoc)
            ->replace(['/**', '*/'], '')
            ->explode("\n")
            ->first(fn ($str) => Str::is(['*@property*$resource', '*@mixin*'], $str));

        if ($mixinOrPropertyLine) {
            $modelName = Str::replace(['@property-read', '@property', '$resource', '@mixin', ' ', '*', "\r"], '', $mixinOrPropertyLine);

            $modelClass = $getFqName($modelName);

            if (class_exists($modelClass)) {
                return $modelClass;
            }
        }

        return null; // TODO: determine whether we want to support implicit resource models.
    }
}