<?php


namespace SimplyCodedSoftware\Messaging\Endpoint\Interceptor;

use SimplyCodedSoftware\Messaging\Endpoint\ConsumerInterceptor;
use SimplyCodedSoftware\Messaging\Message;

/**
 * Class RunLimitConsumerInterceptor
 * @package SimplyCodedSoftware\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class RunLimitConsumerInterceptor implements ConsumerInterceptor
{
    /**
     * @var int
     */
    private $runLimitAmount;
    /**
     * @var int
     */
    private $currentLimit = 0;

    /**
     * RunLimitConsumerInterceptor constructor.
     * @param int $runLimitAmount
     */
    private function __construct(int $runLimitAmount)
    {
        $this->runLimitAmount = $runLimitAmount;
    }

    /**
     * @param int $runLimitAmount
     * @return RunLimitConsumerInterceptor
     */
    public static function createWith(int $runLimitAmount) : self
    {
        return new self($runLimitAmount);
    }

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return $this->currentLimit > $this->runLimitAmount;
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
        $this->currentLimit++;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
        return;
    }
}