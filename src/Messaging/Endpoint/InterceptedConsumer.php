<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;

/**
 * Class ContinuouslyRunningConsumer
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class InterceptedConsumer implements ConsumerLifecycle
{
    /**
     * @var ConsumerLifecycle
     */
    private $interceptedConsumer;
    /**
     * @var iterable|ConsumerInterceptor[]
     */
    private $consumerInterceptors;
    /**
     * @var bool
     */
    private $shouldBeRunning = true;

    /**
     * ContinuouslyRunningConsumer constructor.
     * @param ConsumerLifecycle $consumerLifecycle
     * @param ConsumerInterceptor[] $consumerInterceptors
     */
    private function __construct(ConsumerLifecycle $consumerLifecycle, iterable $consumerInterceptors)
    {
        $this->interceptedConsumer = $consumerLifecycle;
        $this->consumerInterceptors = $consumerInterceptors;
    }

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     * @param PollingMetadata|null $pollingMetadata
     * @return ConsumerLifecycle
     */
    public static function createWith(ConsumerLifecycle $consumerLifecycle, ?PollingMetadata $pollingMetadata) : ConsumerLifecycle
    {
        if (!$pollingMetadata) {
            return new self($consumerLifecycle, []);
        }

        $interceptors = [];
        if($pollingMetadata->getStopAfterExceedingHandledMessageLimit() > 0) {
            $interceptors[] = new LimitConsumedMessagesInterceptor($pollingMetadata->getStopAfterExceedingHandledMessageLimit());
        }

        return new self($consumerLifecycle, $interceptors);
    }

    /**
     * @inheritDoc
     */
    public function run(): void
    {
        foreach ($this->consumerInterceptors as $consumerInterceptor) {
            $consumerInterceptor->onStartup();
        }

        while ($this->shouldBeRunning()) {
            foreach ($this->consumerInterceptors as $consumerInterceptor) {
                $consumerInterceptor->preRun();
            }
            $this->interceptedConsumer->run();
            foreach ($this->consumerInterceptors as $consumerInterceptor) {
                $consumerInterceptor->postRun();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function stop(): void
    {
        $this->shouldBeRunning = false;
    }

    /**
     * @inheritDoc
     */
    public function isRunningInSeparateThread(): bool
    {
        return $this->interceptedConsumer->isRunningInSeparateThread();
    }

    /**
     * @inheritDoc
     */
    public function getConsumerName(): string
    {
        return $this->interceptedConsumer->getConsumerName();
    }

    /**
     * @return bool
     */
    private function shouldBeRunning() : bool
    {
        if (!$this->shouldBeRunning) {
            return false;
        }

        foreach ($this->consumerInterceptors as $consumerInterceptor) {
            if ($consumerInterceptor->shouldBeStopped()) {
                return false;
            }
        }

        return true;
    }
}