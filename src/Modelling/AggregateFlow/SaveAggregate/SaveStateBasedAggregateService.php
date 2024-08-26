<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyEditorAccessor;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Modelling\SaveAggregateService;
use Ecotone\Modelling\StandardRepository;

/**
 * licence Apache-2.0
 */
final class SaveStateBasedAggregateService implements SaveAggregateService
{
    public function __construct(
        private string                 $calledClass,
        private bool                   $isFactoryMethod,
        private StandardRepository     $aggregateRepository,
        private PropertyEditorAccessor $propertyEditorAccessor,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private array                  $aggregateIdentifierMapping,
        private array                  $aggregateIdentifierGetMethods,
        private ?string                $aggregateVersionProperty,
        private bool                   $isAggregateVersionAutomaticallyIncreased
    ) {
    }

    public function process(Message $message): Message
    {
        $aggregate = SaveAggregateServiceTemplate::resolveAggregate($this->calledClass, $message, $this->isFactoryMethod);
        $metadata = $message->getHeaders()->headers();
        $versionBeforeHandling = SaveAggregateServiceTemplate::resolveVersionBeforeHandling($message);
        SaveAggregateServiceTemplate::enrichVersionIfNeeded(
            $this->propertyEditorAccessor,
            $versionBeforeHandling,
            $aggregate,
            $message,
            $this->aggregateVersionProperty,
            $this->isAggregateVersionAutomaticallyIncreased
        );

        $aggregateIds = $this->getAggregateIds($metadata, $aggregate, false);
        $this->aggregateRepository->save($aggregateIds, $aggregate, MessageHeaders::unsetNonUserKeys($metadata), $versionBeforeHandling);

        $aggregateIds = $this->getAggregateIds($metadata, $aggregate, true);

        return SaveAggregateServiceTemplate::buildReplyMessage(
            $this->isFactoryMethod,
            $aggregateIds,
            $message,
        );
    }

    private function getAggregateIds(array $metadata, object|string $aggregate, bool $throwOnNoIdentifier): array
    {
        return SaveAggregateServiceTemplate::getAggregateIds(
            $this->propertyReaderAccessor,
            $metadata,
            $this->calledClass,
            $this->aggregateIdentifierMapping,
            $this->aggregateIdentifierGetMethods,
            $aggregate,
            $throwOnNoIdentifier
        );
    }
}
