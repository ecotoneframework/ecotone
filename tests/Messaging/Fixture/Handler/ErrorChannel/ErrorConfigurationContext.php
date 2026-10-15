<?php

declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Handler\ErrorChannel;

use Ecotone\Api\Attribute\ServiceContext;
use Ecotone\Api\ExtensionObject\ErrorHandlerConfiguration;
use Ecotone\Api\ExtensionObject\PollingMetadata;
use Ecotone\Api\ExtensionObject\RetryTemplateBuilder;

/**
 * licence Apache-2.0
 */
class ErrorConfigurationContext
{
    public const INPUT_CHANNEL = 'correctOrders';
    public const ERROR_CHANNEL = 'errorChannel';

    #[ServiceContext]
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::create(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackOff(1, 1)
                ->maxRetries(2)
        );
    }

    #[ServiceContext]
    public function pollingConfiguration()
    {
        return [
            PollingMetadata::create(self::INPUT_CHANNEL)
                ->withExecutionTimeLimitInMilliseconds(3000)
                ->withHandledMessageLimit(1)
                ->withErrorChannelName(self::ERROR_CHANNEL)
                ->withStopOnError(false),
        ];
    }
}
