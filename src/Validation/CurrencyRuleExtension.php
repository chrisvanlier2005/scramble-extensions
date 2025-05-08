<?php

namespace Lier\ScrambleExtensions\Validation;

use Dedoc\Scramble\Support\Generator\Types\Type as OpenApiType;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\Rules\ValidationRuleExtension;
use Dedoc\Scramble\Support\Type\Generic;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

final class CurrencyRuleExtension extends ValidationRuleExtension
{
    public static string $ruleName = 'App\Rules\CurrencyRule';

    /**
     * Determine whether this extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $rule
     * @return bool
     */
    public function shouldHandle(mixed $rule): bool
    {
        return $rule instanceof ObjectType
            && $rule->isInstanceOf(self::$ruleName);
    }

    /**
     * Handle the given type and return the transformed OpenAPI type.
     *
     * @param \Dedoc\Scramble\Support\Generator\Types\Type $previousType
     * @param \Dedoc\Scramble\Support\Type\Type $rule
     * @return \Dedoc\Scramble\Support\Generator\Types\Type
     */
    public function handle(OpenApiType $previousType, mixed $rule): OpenApiType
    {
        $type = $this->openApiTransformer->transform(new ObjectType(
            '\Brick\Money\Currency',
        ));

        $type->setAttribute('required', $previousType->getAttribute('required', false));
        $type->nullable($previousType->nullable);

        return $type;
    }
}
