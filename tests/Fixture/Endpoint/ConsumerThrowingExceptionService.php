<?php
declare(strict_types=1);

namespace Fixture\Endpoint;
use SimplyCodedSoftware\IntegrationMessaging\Endpoint\ConsumerLifecycle;

/**
 * Class InboundChannelAdapterStoppingService
 * @package Fixture\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class ConsumerThrowingExceptionService
{
    /**
     * @var ConsumerLifecycle
     */
    private $consumerLifecycle;

    /**
     * InboundChannelAdapterStoppingService constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return self
     */
    public static function create() : self
    {
        return new self();
    }

    public function execute() : void
    {
        $this->consumerLifecycle->stop();

        throw new \RuntimeException("Test error. This should be caught");
    }

    /**
     * @param ConsumerLifecycle $consumerLifecycle
     */
    public function setConsumerLifecycle(ConsumerLifecycle $consumerLifecycle) : void
    {
        $this->consumerLifecycle = $consumerLifecycle;
    }
}