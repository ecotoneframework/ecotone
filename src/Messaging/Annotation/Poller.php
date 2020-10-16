<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Annotation;
use Ecotone\Messaging\Endpoint\PollingMetadata;

/**
 * Class PollingMetadata
 * @package Ecotone\Messaging\Annotation
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 * @Annotation
 */
class Poller
{
    public string $cron = "";
    public string $errorChannelName = "";
    public int $maxMessagePerPoll = PollingMetadata::DEFAULT_MAX_MESSAGES_PER_POLL;
    public string $triggerReferenceName = "";
    public string $taskExecutorName = "";
    /**
     * @var int in milliseconds
     */
    public int $fixedRateInMilliseconds = PollingMetadata::DEFAULT_FIXED_RATE;
    /**
     * @var int in milliseconds
     */
    public int $initialDelayInMilliseconds = PollingMetadata::DEFAULT_INITIAL_DELAY;
    /**
     * How much ram can poller use before stopping
     */
    public int $memoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES;
    /**
     * How many messages should poller handle before stopping
     */
    public int $handledMessageLimit = PollingMetadata::DEFAULT_HANDLED_MESSAGE_LIMIT;
    /**
     * How long should poller handle messages before stopping
     */
    public int $executionTimeLimitInMilliseconds = PollingMetadata::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS;
    /**
     * Consumer will throw exception if there is a problem during polling from the channel
     * However you change default mechanism and add retries, before consumer will fail
     */
    public \Ecotone\Messaging\Annotation\RetryTemplate $channelPollRetryTemplate;
}