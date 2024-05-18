<?php

declare(strict_types=1);

namespace Ecotone\Modelling\AggregateFlow\SaveAggregate;

use Ecotone\Messaging\Message;
use Ecotone\Messaging\MessageHeaders;
use Ecotone\Messaging\Support\MessageBuilder;
use Ecotone\Modelling\AggregateMessage;
use Ecotone\Modelling\SaveAggregateService;

final class SaveMultipleAggregateService implements SaveAggregateService
{
    public function __construct(
        private SaveAggregateService $saveCalledAggregateService,
        private SaveAggregateService $saveResultAggregateService,
    ) {
    }

    public function save(Message $message, array $metadata): Message
    {
        if ($message->getHeaders()->containsKey(AggregateMessage::CALLED_AGGREGATE_OBJECT)) {
            $this->saveCalledAggregateService->save($message, $metadata);
        }

        if ($message->getHeaders()->containsKey(AggregateMessage::RESULT_AGGREGATE_OBJECT)) {
            $metadata = MessageHeaders::unsetAggregateKeys($metadata);
            $metadata[AggregateMessage::RESULT_AGGREGATE_OBJECT] = $message->getHeaders()->get(AggregateMessage::RESULT_AGGREGATE_OBJECT);
            $this->saveResultAggregateService->save($message, $metadata);
        }

        return MessageBuilder::fromMessage($message)->build();
    }
}
