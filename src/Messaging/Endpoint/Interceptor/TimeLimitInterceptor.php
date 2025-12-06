<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;
use Ecotone\Messaging\Scheduling\DatePoint;
use Ecotone\Messaging\Scheduling\Duration;
use Ecotone\Messaging\Scheduling\EcotoneClockInterface;

/**
 * Class LimitConsumedMessagesExtension
 * @package Ecotone\Messaging\Endpoint\Extension
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class TimeLimitInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;

    private ?DatePoint $startTime;
    private Duration $timeout;

    /**
     * LimitMemoryUsageInterceptor constructor.
     * @param int $milliseconds
     * @throws \Ecotone\Messaging\MessagingException
     */
    public function __construct(private EcotoneClockInterface $clock, int $milliseconds)
    {
        if ($milliseconds <= 0) {
            throw ConfigurationException::create("Time limit is set to incorrect value: {$milliseconds}");
        }
        $this->timeout = Duration::milliseconds($milliseconds);
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        $this->startTime = $this->clock->now();
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return $this->clock->now()->durationSince($this->startTime)->isGreaterThan($this->timeout);
    }
}
