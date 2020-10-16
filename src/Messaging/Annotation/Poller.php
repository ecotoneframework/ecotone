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
    /**
     * @var string
     */
    public $cron = "";
    /**
     * @var string
     */
    public $errorChannelName = "";
    /**
     * @var int
     */
    public $maxMessagePerPoll = PollingMetadata::DEFAULT_MAX_MESSAGES_PER_POLL;
    /**
     * @var string
     */
    public $triggerReferenceName = "";
    /**
     * @var string
     */
    public $taskExecutorName = "";
    /**
     * @var int in milliseconds
     */
    public $fixedRateInMilliseconds = PollingMetadata::DEFAULT_FIXED_RATE;
    /**
     * @var int in milliseconds
     */
    public $initialDelayInMilliseconds = PollingMetadata::DEFAULT_INITIAL_DELAY;
    /**
     * How much ram can poller use before stopping
     *
     * @var int
     */
    public $memoryLimitInMegabytes = PollingMetadata::DEFAULT_MEMORY_LIMIT_MEGABYTES;
    /**
     * How many messages should poller handle before stopping
     *
     * @var int
     */
    public $handledMessageLimit = PollingMetadata::DEFAULT_HANDLED_MESSAGE_LIMIT;
    /**
     * How long should poller handle messages before stopping
     *
     * @var int
     */
    public $executionTimeLimitInMilliseconds = PollingMetadata::DEFAULT_EXECUTION_TIME_LIMIT_IN_MILLISECONDS;
    /**
     * Consumer will throw exception if there is a problem during polling from the channel
     * However you change default mechanism and add retries, before consumer will fail
     *
     * @var \Ecotone\Messaging\Annotation\RetryTemplate
     */
    public $channelPollRetryTemplate;
}