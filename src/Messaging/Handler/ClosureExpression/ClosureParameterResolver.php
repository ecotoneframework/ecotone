<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use function array_key_exists;

use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\InvalidArgumentException;

/**
 * licence Enterprise
 */
final class ClosureParameterResolver
{
    public function __construct(
        private string $parameterName,
        private ?ParameterConverter $parameterConverter,
        private bool $resolvesFromAdditionalContext,
        private bool $hasDefaultValue,
        private mixed $defaultValue,
    ) {
    }

    public function resolve(Message $message, array $additionalContext): mixed
    {
        if ($this->resolvesFromAdditionalContext && array_key_exists($this->parameterName, $additionalContext)) {
            return $additionalContext[$this->parameterName];
        }
        if ($this->parameterConverter !== null) {
            return $this->parameterConverter->getArgumentFrom($message);
        }
        if ($this->hasDefaultValue) {
            return $this->defaultValue;
        }

        throw InvalidArgumentException::create("Cannot resolve parameter `{$this->parameterName}` of closure expression. Use #[Payload], #[Header], #[Headers], #[Reference] or #[ConfigurationVariable] to define how it should be resolved.");
    }
}
