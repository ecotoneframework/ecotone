<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use function array_key_exists;

use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function is_string;

/**
 * Carries attribute together with compiled expression bound to plain context variables,
 * for evaluation without Message. Closure expressions bind context variables by parameter name,
 * string expressions evaluate with Expression Language.
 */
/**
 * licence Enterprise
 */
final class AttributeExpressionContextExecutor
{
    private Closure|string $expression;

    /**
     * @param array<array{name: string, hasDefaultValue: bool, defaultValue: mixed}> $parameterSpecifications
     */
    public function __construct(
        WithExpression $attribute,
        private ExpressionEvaluationService $expressionEvaluationService,
        private array $parameterSpecifications,
    ) {
        $expression = $attribute->getExpression();
        if ($expression === null || $expression === '') {
            throw InvalidArgumentException::create(sprintf('Attribute %s has no expression to execute', get_class($attribute)));
        }

        $this->expression = $expression;
    }

    public function execute(array $context): mixed
    {
        if (is_string($this->expression)) {
            return $this->expressionEvaluationService->evaluateWithContext($this->expression, $context);
        }

        $arguments = [];
        foreach ($this->parameterSpecifications as $index => $parameterSpecification) {
            if (array_key_exists($parameterSpecification['name'], $context)) {
                $arguments[] = $context[$parameterSpecification['name']];
            } elseif ($index === 0 && array_key_exists('payload', $context)) {
                $arguments[] = $context['payload'];
            } elseif ($parameterSpecification['hasDefaultValue']) {
                $arguments[] = $parameterSpecification['defaultValue'];
            } else {
                throw InvalidArgumentException::create(sprintf('Cannot resolve parameter `%s` of closure expression. Available context variables: %s', $parameterSpecification['name'], implode(', ', array_keys($context))));
            }
        }

        return ($this->expression)(...$arguments);
    }
}
