<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\MessageProcessor;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\NullableMessageChannel;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateFlow\AggregateIdMetadata;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\AggregateNotFoundException;
use Ecotone\Modelling\Repository\AggregateRepository;

/**
 * licence Apache-2.0
 */
final class LoadAggregateMessageProcessor implements MessageProcessor
{
    public function __construct(
        private AggregateRepository $repository,
        private string $aggregateClassName,
        private string $aggregateMethod,
        private ?string $messageVersionPropertyName,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private LoadAggregateMode $loadAggregateMode
    ) {
    }

    public function process(Message $message): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($message);
        $messageType = Type::createFromVariable($message->getPayload());

        if (! $message->getHeaders()->containsKey(AggregateMessage::AGGREGATE_ID)) {
            throw AggregateNotFoundException::create(sprintf("Can't call Aggregate {$this->aggregateClassName}:{$this->aggregateMethod} as identifier header is missing. Please check your identifier mapping in {$messageType->toString()}. Have you forgot to add #[TargetIdentifier] in your Command or `%s` in metadata?", AggregateMessage::AGGREGATE_ID));
        }

        $aggregateIdentifiers = AggregateIdMetadata::createFrom(
            $message->getHeaders()->get(AggregateMessage::AGGREGATE_ID)
        )->getIdentifiers();

        foreach ($aggregateIdentifiers as $identifierName => $aggregateIdentifier) {
            if (is_null($aggregateIdentifier)) {
                throw AggregateNotFoundException::create(sprintf("Can't call Aggregate {$this->aggregateClassName}:{$this->aggregateMethod} as value for identifier `{$identifierName}` is missing. Please check your identifier mapping in {$messageType->toString()}. Have you forgot to add #[TargetIdentifier] in your Command or `%s` in metadata?", AggregateMessage::AGGREGATE_ID));
            }
        }

        if (! is_null($this->messageVersionPropertyName)) {
            $expectedVersion = null;
            if ($this->propertyReaderAccessor->hasPropertyValue(PropertyPath::createWith($this->messageVersionPropertyName), $message->getPayload())) {
                $expectedVersion = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($this->messageVersionPropertyName), $message->getPayload());
            }

            $resultMessage = $resultMessage->setHeader(AggregateMessage::TARGET_VERSION, $expectedVersion);
        }

        $aggregate = $this->repository->findBy($this->aggregateClassName, $aggregateIdentifiers);

        if (! $aggregate && $this->loadAggregateMode->isDroppingMessageOnNotFound()) {
            return null;
        }

        if (! $aggregate && $this->loadAggregateMode->isThrowingOnNotFound()) {
            if ($aggregateIdentifiers === []) {
                throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not as no identifiers were provided. Have you forgot to add use #[TargetIdentifier] in your Command or `aggregate.id` in metadata or provide #[Identifier] to MessageGateway?");
            }

            throw AggregateNotFoundException::create("Aggregate {$this->aggregateClassName} for calling {$this->aggregateMethod} was not found using identifiers " . json_encode($aggregateIdentifiers));
        }

        if ($aggregate) {
            $resultMessage = $resultMessage->setHeader(AggregateMessage::CALLED_AGGREGATE_INSTANCE, $aggregate->getAggregateInstance());
        }

        if (! $message->getHeaders()->containsKey(MessageHeaders::REPLY_CHANNEL)) {
            $resultMessage = $resultMessage->setReplyChannel(NullableMessageChannel::create());
        }

        return $resultMessage->build();
    }
}
