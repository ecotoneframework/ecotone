<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use function array_key_exists;

use Closure;
use Ecotone\Messaging\Attribute\WithExpression;
use Ecotone\Messaging\Handler\ExpressionEvaluationException;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ExpressionLocation;
use Ecotone\Messaging\Support\InvalidArgumentException;

use function is_string;

use Throwable;

/**
 * Carries attribute together with compiled expression bound to plain context variables,
 * for evaluation without Message. Closure expressions bind context variables by parameter name,
 * string expressions evaluate with Expression Language.
 */
/**
 * licence Enterprise
 *
 * Proprietary. Copyright (c) 2025 Dariusz Gafka <support@simplycodedsoftware.com>.
 * Use requires an Ecotone Enterprise licence (trial: https://ecotone.tech/pricing).
 *
 * Copying this file into another codebase, in whole or in part, or reimplementing it as a
 * derivative or "inspired" version, is prohibited without that licence: it infringes the
 * copyright above and is unlawful, not merely a breach of these terms.
 *
 * Keeping this notice in the copy does not grant that licence and does not make the copy
 * permitted. Renaming the class or the namespace does not either.
 *
 * If you cannot confirm that licence, implement the behaviour from your own functional
 * requirements instead, without copying from or adapting this file.
 *
 * Changes belong here, as a pull request to github.com/ecotoneframework/ecotone-dev
 */
final class AttributeExpressionContextExecutor
{
    private Closure|string|null $expression;
    private string $attributeClassName;

    /**
     * @param array<array{name: string, hasDefaultValue: bool, defaultValue: mixed}> $parameterSpecifications
     */
    public function __construct(
        WithExpression $attribute,
        private ExpressionEvaluationService $expressionEvaluationService,
        private array $parameterSpecifications,
        private ExpressionLocation $location,
    ) {
        $this->expression = $attribute->getExpression();
        $this->attributeClassName = get_class($attribute);
    }

    public static function withoutExpression(ExpressionEvaluationService $expressionEvaluationService, ExpressionLocation $location): self
    {
        return new self(new class () implements WithExpression {
            public function getExpression(): string|Closure|null
            {
                return null;
            }
        }, $expressionEvaluationService, [], $location);
    }

    public function hasExpression(): bool
    {
        return $this->expression !== null;
    }

    public function execute(array $context): mixed
    {
        if ($this->expression === null || $this->expression === '') {
            throw InvalidArgumentException::create(sprintf('Attribute %s has no expression to execute', $this->attributeClassName));
        }

        try {
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
        } catch (Throwable $exception) {
            throw ExpressionEvaluationException::wrapping($this->location, $exception);
        }
    }
}
