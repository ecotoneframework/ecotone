<?php

declare(strict_types=1);

namespace Test\Ecotone\Amqp\Fixture\ErrorChannel;

use Ecotone\Amqp\AmqpBackedMessageChannelBuilder;
use Ecotone\Amqp\Configuration\AmqpConfiguration;
use Ecotone\Messaging\Attribute\ServiceContext;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ErrorConfigurationContext
{
    public const INPUT_CHANNEL = 'correctOrders';
    public const ERROR_CHANNEL = 'errorChannel';


    #[ServiceContext]
    public function getChannels()
    {
        return [
            AmqpBackedMessageChannelBuilder::create(self::INPUT_CHANNEL)
                ->withReceiveTimeout(1),
        ];
    }

    #[ServiceContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::create(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 1)
                ->maxRetryAttempts(2)
        );
    }

    #[ServiceContext]
    public function pollingConfiguration()
    {
        return [
            PollingMetadata::create(self::INPUT_CHANNEL)
                ->setExecutionTimeLimitInMilliseconds(3000)
                ->setHandledMessageLimit(1)
                ->setErrorChannelName(self::ERROR_CHANNEL),
        ];
    }

    #[ServiceContext]
    public function registerAmqpConfig(): array
    {
        return [
            AmqpConfiguration::createWithDefaults()
                ->withTransactionOnAsynchronousEndpoints(true)
                ->withTransactionOnCommandBus(true),
        ];
    }
}
