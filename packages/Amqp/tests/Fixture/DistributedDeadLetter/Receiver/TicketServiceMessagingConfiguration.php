<?php

namespace Test\Ecotone\Amqp\Fixture\DistributedDeadLetter\Receiver;

use Ecotone\Amqp\Distribution\AmqpDistributedBusConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class TicketServiceMessagingConfiguration
{
    public const SERVICE_NAME = 'ticket_service';
    public const ERROR_CHANNEL = 'error_channel';
    public const DEAD_LETTER_CHANNEL = 'dead_letter';

    #[ServiceContext]
    public function configure()
    {
        return [
            AmqpDistributedBusConfiguration::createConsumer(),
            PollingMetadata::create(self::SERVICE_NAME)
                ->setHandledMessageLimit(1)
                ->setExecutionTimeLimitInMilliseconds(1000)
                ->setErrorChannelName(self::ERROR_CHANNEL),
        ];
    }

    #[ServiceContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(1),
            self::DEAD_LETTER_CHANNEL
        );
    }
}
