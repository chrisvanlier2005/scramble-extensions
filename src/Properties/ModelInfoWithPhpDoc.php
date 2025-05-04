<?php

namespace Lier\ScrambleExtensions\Properties;

use Dedoc\Scramble\PhpDoc\PhpDocTypeHelper;
use Dedoc\Scramble\Support\PhpDoc;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;
use ReflectionClass;

/**
 * @internal Mostly copied over from {@see \Dedoc\Scramble\Support\ResponseExtractor\ModelInfo}
 */
class ModelInfoWithPhpDoc
{
    /**
     * Create a new instance.
     *
     * @param string $class
     * @return void
     */
    public function __construct(
        private string $class,
    ) {
    }

    /**
     * Handle the model information retrieval.
     *
     * @return \Lier\ScrambleExtensions\Properties\ModelInfo
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     * @throws \ReflectionException
     */
    public function handle(): ModelInfo
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $class */
        $class = $this->qualifyModel($this->class);

        $reflectionClass = new ReflectionClass($class);

        if (!$reflectionClass->isInstantiable()) {
            return new ModelInfo();
        }

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = app()->make($class);

        return new ModelInfo($this->getPhpdocAttributes($model));
    }

    /**
     * Qualify the given model class base name.
     *
     * @see \Dedoc\Scramble\Support\ResponseExtractor\ModelInfo::qualifyModel()
     * @param string $model
     * @return string
     * @see \Illuminate\Console\GeneratorCommand
     */
    protected function qualifyModel(string $model): string
    {
        if (class_exists($model)) {
            return $model;
        }

        $model = ltrim($model, '\\/');
        $model = str_replace('/', '\\', $model);

        $rootNamespace = app()->getNamespace();

        if (str_starts_with($model, $rootNamespace)) {
            return $model;
        }

        return is_dir(app_path('Models'))
            ? $rootNamespace . 'Models\\' . $model
            : $rootNamespace . $model;
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Support\Collection<string, \Lier\ScrambleExtensions\Properties\PhpDocAttribute>
     */
    private function getPhpdocAttributes(Model $model): Collection
    {
        $phpdoc = new ReflectionClass($model)->getDocComment();

        if ($phpdoc === false) {
            return new Collection();
        }

        return new Collection(PhpDoc::parse($phpdoc)->children)
            ->whereInstanceOf(PhpDocTagNode::class)
            ->filter(fn (PhpDocTagNode $tag) => $tag->name === '@property' || $tag->name === '@property-read')
            ->filter(fn (PhpDocTagNode $tag) => $tag->value instanceof PropertyTagValueNode)
            ->mapWithKeys(function (PhpDocTagNode $tag) {
                /** @var \PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode&object{type: \PHPStan\PhpDocParser\Ast\Type\TypeNode, propertyName: string} $value */
                $value = $tag->value;

                $type = PhpDocTypeHelper::toType($value->type);

                $name = substr($value->propertyName, 1); // Remove the leading '$' character.

                return [
                    $name => new PhpDocAttribute(
                        name: $name,
                        type: $type,
                        isReadOnly: $tag->name === '@property-read',
                        node: $tag,
                    ),
                ];
            });
    }
}
