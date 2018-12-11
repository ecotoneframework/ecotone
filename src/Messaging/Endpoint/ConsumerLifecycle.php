<?php
declare(strict_types=1);

namespace SimplyCodedSoftware\Messaging\Endpoint;

/**
 * Interface Lifecycle
 * @package SimplyCodedSoftware\Messaging\Endpoint
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
interface ConsumerLifecycle
{
    /**
     * start component
     */
    public function start() : void;

    /**
     * stop component from running
     */
    public function stop() : void;

    /**
     * @return bool
     */
    public function isRunningInSeparateThread() : bool;

    /**
     * @return string
     */
    public function getConsumerName() : string;
}