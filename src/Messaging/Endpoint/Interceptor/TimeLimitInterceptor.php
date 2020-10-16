<?php
declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class LimitConsumedMessagesExtension
 * @package Ecotone\Messaging\Endpoint\Extension
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class TimeLimitInterceptor implements ConsumerInterceptor
{
    /**
     * @var int
     */
    private $milliseconds;
    private ?float $startTime;

    /**
     * LimitMemoryUsageInterceptor constructor.
     * @param int $milliseconds
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(int $milliseconds)
    {
        if ($milliseconds <= 0) {
            throw ConfigurationException::create("Tim limit is set to incorrect value: {$milliseconds}");
        }

        $this->milliseconds = $milliseconds;
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->startTime = microtime(true) * 1000;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        $currentTime = microtime(true) * 1000;

        return ($currentTime - $this->startTime) > $this->milliseconds;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function shouldBeThrown(\Throwable $exception) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
    }
}