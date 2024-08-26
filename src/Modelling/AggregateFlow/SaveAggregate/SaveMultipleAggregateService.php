<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\GenericMessage;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\SaveAggregateService;

/**
 * licence Apache-2.0
 */
final class SaveMultipleAggregateService implements SaveAggregateService
{
    public function __construct(
        private SaveAggregateService $saveCalledAggregateService,
        private SaveAggregateService $saveResultAggregateService,
    ) {
    }

    public function process(Message $message): Message
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT)) {
            $this->saveCalledAggregateService->process($message);
        }

        if ($message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT)) {
            $metadata = MessageHeaders::unsetAggregateKeys($message->getHeaders()->headers());
            $metadata[AggregateMessage::RESULT_AGGREGATE_OBJECT] = $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_OBJECT);
            if ($message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_EVENTS)) {
                $metadata[AggregateMessage::RESULT_AGGREGATE_EVENTS] = $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_EVENTS);
            }
            $saveResultAggregateMessage = GenericMessage::create(
                $message->getPayload(),
                MessageHeaders::create($metadata)
            );
            $this->saveResultAggregateService->process($saveResultAggregateMessage);
        }

        return $message;
    }
}
