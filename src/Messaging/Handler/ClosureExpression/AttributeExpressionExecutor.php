<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function is_string;

/**
 * Carries intercepted attribute together with compiled expression program.
 * Closure expressions execute with parameter resolvers compiled at container build time,
 * string expressions evaluate with Expression Language.
 */
/**
 * licence Enterprise
 */
final class AttributeExpressionExecutor
{
    private Closure|string|null $expression;

    /**
     * @param ClosureParameterResolver[] $closureParameterResolvers
     */
    public function __construct(
        private object $attribute,
        private ExpressionEvaluationService $expressionEvaluationService,
        private array $closureParameterResolvers = [],
    ) {
        $this->expression = $attribute instanceof WithExpression ? $attribute->getExpression() : null;
    }

    public function getAttribute(): object
    {
        return $this->attribute;
    }

    public function hasExpression(): bool
    {
        return $this->expression !== null && $this->expression !== '';
    }

    public function execute(Message $message, array $additionalContext = []): mixed
    {
        $expression = $this->expression;
        if ($expression instanceof Closure) {
            $arguments = [];
            foreach ($this->closureParameterResolvers as $parameterResolver) {
                $arguments[] = $parameterResolver->resolve($message, $additionalContext);
            }

            return $expression(...$arguments);
        }
        if (is_string($expression) && $expression !== '') {
            return $this->expressionEvaluationService->evaluateWithMessage($expression, $message, $additionalContext);
        }

        throw InvalidArgumentException::create(sprintf('Attribute %s has no expression to execute', get_class($this->attribute)));
    }
}
