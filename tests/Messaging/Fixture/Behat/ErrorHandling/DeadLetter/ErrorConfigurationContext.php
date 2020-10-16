<?php
declare(strict_types=1);

namespace Test\Ecotone\Messaging\Fixture\Behat\ErrorHandling\DeadLetter;

use Ecotone\Messaging\Annotation\ApplicationContext;
use Ecotone\Messaging\Channel\SimpleMessageChannelBuilder;
use Ecotone\Messaging\Endpoint\PollingMetadata;
use Ecotone\Messaging\Handler\Recoverability\ErrorHandlerConfiguration;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

class ErrorConfigurationContext
{
    const INPUT_CHANNEL       = "inputChannel";
    const ERROR_CHANNEL       = "errorChannel";
    const DEAD_LETTER_CHANNEL = "deadLetterChannel";

    /**
     * @ApplicationContext()
     */
    public function getInputChannel()
    {
        return SimpleMessageChannelBuilder::createQueueChannel(self::INPUT_CHANNEL);
    }

    /**
     * @ApplicationContext()
     */
    public function errorConfiguration()
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            self::ERROR_CHANNEL,
            RetryTemplateBuilder::exponentialBackoff(1, 2)
                ->maxRetryAttempts(2),
            self::DEAD_LETTER_CHANNEL
        );
    }

    /**
     * @ApplicationContext()
     */
    public function pollingConfiguration()
    {
        return PollingMetadata::create("orderService")
            ->setExecutionTimeLimitInMilliseconds(1)
            ->setHandledMessageLimit(1)
            ->setErrorChannelName(ErrorConfigurationContext::ERROR_CHANNEL);
    }
}