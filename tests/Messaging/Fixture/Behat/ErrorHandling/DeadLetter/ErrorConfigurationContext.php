<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;
use Ecotone\Api\ExtensionObject\SimpleMessageChannelBuilder;

/**
 * licence Apache-2.0
 */
class ErrorConfigurationContext
{
    public const INPUT_CHANNEL       = 'inputChannel';
    public const ERROR_CHANNEL       = 'errorChannel';
    public const DEAD_LETTER_CHANNEL = 'deadLetterChannel';

    #[ServiceContext]
    public function getInputChannel()
    {
        return SimpleMessageChannelBuilder::createQueueChannel(self::INPUT_CHANNEL, delayable: false);
    }

    #[ServiceContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackOff(1, 2)
                ->maxRetries(2),
            self::DEAD_LETTER_CHANNEL
        );
    }

    #[ServiceContext]
    public function pollingConfiguration()
    {
        return PollingMetadata::create('orderService')
            ->withExecutionTimeLimitInMilliseconds(100)
            ->withHandledMessageLimit(1)
            ->withErrorChannelName(ErrorConfigurationContext::ERROR_CHANNEL);
    }
}
