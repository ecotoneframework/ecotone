<?php

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
     * Is component running
     *
     * @return bool
     */
    public function isRunning() : bool;

    /**
     * @return string
     */
    public function getComponentName() : string;
}