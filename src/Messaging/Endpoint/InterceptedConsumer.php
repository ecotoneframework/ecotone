<?php

namespace SimplyCodedSoftware\Messaging\Endpoint;

use SimplyCodedSoftware\Messaging\Endpoint\Interceptor\LimitConsumedMessagesInterceptor;
use SimplyCodedSoftware\Messaging\Endpoint\Interceptor\LimitMemoryUsageInterceptor;
use SimplyCodedSoftware\Messaging\Endpoint\Interceptor\SignalInterceptor;
use SimplyCodedSoftware\Messaging\Handler\Processor\MethodInvoker\AroundInterceptorReference;

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
     * @param ConsumerLifecycleBuilder $consumerLifecycleBuilder
     * @param PollingMetadata|null $pollingMetadata
     * @param \Closure $buildContext
     * @return ConsumerLifecycle
     * @throws \SimplyCodedSoftware\Messaging\MessagingException
     */
    public static function createWith(ConsumerLifecycleBuilder $consumerLifecycleBuilder, ?PollingMetadata $pollingMetadata, \Closure $buildContext) : ConsumerLifecycle
    {
        if (!$pollingMetadata) {
            return new self($buildContext(), []);
        }

        $interceptors = [];
        if($pollingMetadata->getHandledMessageLimit() > 0) {
            $interceptors[] = new LimitConsumedMessagesInterceptor($pollingMetadata->getHandledMessageLimit());
        }
        if ($pollingMetadata->getMemoryLimitInMegabytes() !== 0) {
            $interceptors[] = new LimitMemoryUsageInterceptor($pollingMetadata->getMemoryLimitInMegabytes());
        }
        if ($pollingMetadata->isWithSignalInterceptors()) {
            $interceptor[] = new SignalInterceptor();
        }

        foreach ($interceptors as $interceptor) {
            $consumerLifecycleBuilder->addAroundInterceptor(
                AroundInterceptorReference::createWithDirectObject(
                    "",
                    $interceptor,
                    "postSend",
                    -10000,
                    ""
                )
            );
        }

        $consumerLifecycle = $buildContext();
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