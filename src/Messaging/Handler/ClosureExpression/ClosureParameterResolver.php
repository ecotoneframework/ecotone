<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\ClosureExpression;

use function array_key_exists;

use Ecotone\Api\Messaging\Message;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Support\InvalidArgumentException;

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
