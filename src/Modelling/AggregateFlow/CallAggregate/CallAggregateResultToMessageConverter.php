<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\CallAggregate;

use Ecotone\Messaging\Conversion\MediaType;
use Ecotone\Messaging\Handler\Enricher\PropertyPath;
use Ecotone\Messaging\Handler\Enricher\PropertyReaderAccessor;
use Ecotone\Messaging\Handler\Processor\MethodInvoker\ResultToMessageConverter;
use Ecotone\Messaging\Handler\Type;
use Ecotone\Messaging\Message;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;

/**
 * licence Apache-2.0
 */
final class CallAggregateResultToMessageConverter implements ResultToMessageConverter
{
    public function __construct(
        private ?Type                  $returnType,
        private PropertyReaderAccessor $propertyReaderAccessor,
        private bool                   $isCommandHandler,
        private bool                   $isFactoryMethod,
        private ?string                $aggregateVersionProperty,
    ) {
    }

    public function convertToMessage(Message $requestMessage, mixed $result): ?Message
    {
        $resultMessage = MessageBuilder::fromMessage($requestMessage);

        $resultType = Type::createFromVariable($result);
        if ($resultType->isIterable() && $this->returnType?->isCollection()) {
            $resultType = $this->returnType;
        }

        if ($this->isCommandHandler) {
            $calledAggregate = $requestMessage->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_INSTANCE) ? $requestMessage->getHeaders()->get(AggregateMessage::CALLED_AGGREGATE_INSTANCE) : null;
            $versionBeforeHandling = $requestMessage->getHeaders()->containsKey(AggregateMessage::TARGET_VERSION) ? $requestMessage->getHeaders()->get(AggregateMessage::TARGET_VERSION) : null;

            if (is_null($versionBeforeHandling) && $this->aggregateVersionProperty) {
                if ($this->isFactoryMethod) {
                    $versionBeforeHandling = 0;
                } else {
                    $versionBeforeHandling = $this->propertyReaderAccessor->getPropertyValue(PropertyPath::createWith($this->aggregateVersionProperty), $calledAggregate);
                    $versionBeforeHandling = is_null($versionBeforeHandling) ? 0 : $versionBeforeHandling;
                }

                $resultMessage = $resultMessage->setHeader(AggregateMessage::TARGET_VERSION, $versionBeforeHandling);
            }
        }

        if (! is_null($result)) {
            $resultMessage = $resultMessage
                ->setContentType(MediaType::createApplicationXPHPWithTypeParameter($resultType->toString()))
                ->setPayload($result)
            ;
        }

        if ($this->isCommandHandler && is_null($result)) {
            $resultMessage = $resultMessage->setHeader(AggregateMessage::NULL_EXECUTION_RESULT, true);
        }

        if ($this->isCommandHandler || ! is_null($result)) {
            return $resultMessage->build();
        }

        return null;
    }
}
