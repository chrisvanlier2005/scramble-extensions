<?php

namespace Lier\ScrambleExtensions\Validation;

use Brick\Money\Money;
use Dedoc\Scramble\Support\Generator;
use Dedoc\Scramble\Support\OperationExtensions\RulesExtractor\Rules\ValidationRuleExtension;
use Dedoc\Scramble\Support\Type\ObjectType;
use Dedoc\Scramble\Support\Type\Type;

final class MoneyRuleExtension extends ValidationRuleExtension
{
    public static string $ruleName = 'App\Rules\MoneyRule';

    /**
     * Determine whether this validation rule extension should handle the given type.
     *
     * @param \Dedoc\Scramble\Support\Type\Type $rule
     * @return bool
     */
    public function shouldHandle(Type $rule): bool
    {
        return $rule->isInstanceOf(self::$ruleName);
    }

    /**
     * Handle the given type and return the transformed OpenAPI type.
     *
     * @param \Dedoc\Scramble\Support\Generator\Types\Type $previousType
     * @param \Dedoc\Scramble\Support\Type\Type $rule
     * @return \Dedoc\Scramble\Support\Generator\Types\Type
     */
    public function handle(Generator\Types\Type $previousType, Type $rule): Generator\Types\Type
    {
        $type = $this->openApiTransformer->transform(new ObjectType(
            Money::class,
        ));

        $type->setAttribute('required', $previousType->getAttribute('required', false));
        $type->nullable = $previousType->nullable;

        return $type;
    }
}
