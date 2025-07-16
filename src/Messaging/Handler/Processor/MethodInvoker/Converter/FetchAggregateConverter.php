<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Processor\MethodInvoker\Converter;

use Ecotone\Messaging\Config\LicenceDecider;
use Ecotone\Messaging\Handler\ExpressionEvaluationService;
use Ecotone\Messaging\Handler\ParameterConverter;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\LicensingException;
use Ecotone\Modelling\AggregateFlow\SaveAggregate\AggregateResolver\AggregateDefinitionRegistry;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\Repository\AllAggregateRepository;
use InvalidArgumentException;

/**
 * licence Enterprise
 */
class FetchAggregateConverter implements ParameterConverter
{
    public function __construct(
        private AllAggregateRepository $aggregateRepository,
        private ExpressionEvaluationService $expressionEvaluationService,
        private string $aggregateClassName,
        private string $expression,
        private bool $doesAllowsNull,
        private LicenceDecider $licenceDecider,
        private AggregateDefinitionRegistry $aggregateDefinitionRegistry,
    ) {
    }

    public function getArgumentFrom(Message $message): ?object
    {
        if (! $this->licenceDecider->hasEnterpriseLicence()) {
            throw LicensingException::create('FetchAggregate attribute is available as part of Ecotone Enterprise.');
        }

        /** @var string|string<string, string>|null $identifiers */
        $identifiers = $this->expressionEvaluationService->evaluate(
            $this->expression,
            [
                'value' => $message->getPayload(),
                'headers' => $message->getHeaders()->headers(),
                'payload' => $message->getPayload(),
            ],
        );

        if ($identifiers === null) {
            if (! $this->doesAllowsNull) {
                throw new AggregateNotFoundException("Aggregate {$this->aggregateClassName} was not found as identifiers is null.");
            }

            return null;
        }

        if (! is_array($identifiers)) {
            $identifierMapping = $this->aggregateDefinitionRegistry->getFor($this->aggregateClassName)->getAggregateIdentifierMapping();
            if (count($identifierMapping) > 1) {
                throw new InvalidArgumentException("Can't fetch aggregate {$this->aggregateClassName} as it has multiple identifiers. Please provide array of identifiers.");
            }

            $identifiers = [array_key_first($identifierMapping) => $identifiers];
        }

        $resolvedAggregate = $this->aggregateRepository->findBy(
            $this->aggregateClassName,
            $identifiers
        );

        if (! $resolvedAggregate && ! $this->doesAllowsNull) {
            $identifiersString = is_array($identifiers) ? json_encode($identifiers) : (string) $identifiers;
            throw new AggregateNotFoundException("Aggregate {$this->aggregateClassName} was not found for identifiers {$identifiersString}.");
        }

        return $resolvedAggregate?->getAggregateInstance();
    }
}
