<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateIdResolver;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\NoAggregateFoundToBeSaved;
use Ecotone\Modelling\NoCorrectIdentifierDefinedException;
use Ecotone\Modelling\SaveAggregateService;
use Ecotone\Modelling\StandardRepository;

final class SaveStateBasedAggregateService implements SaveAggregateService
{
    public function __construct(
        private string $calledInterface,
        private bool $isFactoryMethod,
        private StandardRepository $aggregateRepository,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private array $aggregateIdentifierMapping,
        private array $aggregateIdentifierGetMethods,
        private ?string $aggregateVersionProperty,
        private bool $isAggregateVersionAutomaticallyIncreased
    ) {
    }

    public function save(Message $message, array $metadata): Message
    {
        $metadata = MessageHeaders::unsetNonUserKeys($metadata);

        $aggregate = $this->resolveAggregate($message);

        $versionBeforeHandling = $message->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $message->getHeaders()->get(AggregateMessage::TARGET_VERSION) : null;
        if ($this->aggregateVersionProperty && $this->isAggregateVersionAutomaticallyIncreased) {
            $this->propertyEditorAccessor->enrichDataWith(
                PropertyPath::createWith($this->aggregateVersionProperty),
                $aggregate,
                $versionBeforeHandling + 1,
                $message,
                null
            );
        }

        $aggregateIds = $message->getHeaders()->containsKey(AggregateMessage::OVERRIDE_AGGREGATE_IDENTIFIER) ? $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID) : [];
        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate, false);

        $this->aggregateRepository->save($aggregateIds, $aggregate, $metadata, $versionBeforeHandling);

        $aggregateIds = $aggregateIds ?: $this->getAggregateIds($aggregateIds, $aggregate, true);
        if ($this->isFactoryMethod) {
            if (count($aggregateIds) === 1) {
                $aggregateIds = reset($aggregateIds);
            }

            $message = MessageBuilder::fromMessage($message)
                ->setPayload($aggregateIds)
                ->build()
            ;
        }

        return MessageBuilder::fromMessage($message)
            ->build();
    }

    private function getAggregateIds(array $aggregateIds, object $aggregate, bool $throwOnNoIdentifier): array
    {
        foreach ($this->aggregateIdentifierMapping as $aggregateIdName => $aggregateIdValue) {
            if (isset($this->aggregateIdentifierGetMethods[$aggregateIdName])) {
                $id = call_user_func([$aggregate, $this->aggregateIdentifierGetMethods[$aggregateIdName]]);

                if (! is_null($id)) {
                    $aggregateIds[$aggregateIdName] = $id;
                }

                continue;
            }

            $id = $this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                ? $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($aggregateIdName), $aggregate)
                : null;

            if (! $id) {
                if (! $throwOnNoIdentifier) {
                    continue;
                }

                throw NoCorrectIdentifierDefinedException::create("After calling {$this->calledInterface} has no identifier assigned. If you're using Event Sourcing Aggregate, please set up #[EventSourcingHandler] that will assign the id after first event");
            }

            $aggregateIds[$aggregateIdName] = $id;
        }

        return AggregateIdResolver::resolveArrayOfIdentifiers(get_class($aggregate), $aggregateIds);
    }

    private function resolveAggregate(Message $message): object|string
    {
        $messageHeaders = $message->getHeaders();
        if ($this->isFactoryMethod && $messageHeaders->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::RESULT_AGGREGATE_OBJECT);
        }
        if ($messageHeaders->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT)) {
            return $messageHeaders->get(AggregateMessage::CALLED_AGGREGATE_OBJECT);
        }

        throw NoAggregateFoundToBeSaved::create("After calling {$this->calledInterface} no aggregate was found to be saved.");
    }
}
