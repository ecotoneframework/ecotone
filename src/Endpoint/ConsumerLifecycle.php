<?php

namespace Messaging\Endpoint;

/**
 * Interface Lifecycle
 * @package Messaging\Endpoint
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
     * Is component running
     *
     * @return bool
     */
    public function isRunning() : bool;

    /**
     * Checks if component does not miss configuration
     *
     * @return bool
     */
    public function canBeRun() : bool;

    /**
     * Returns information about what configuration is missing
     *
     * @return string
     */
    public function getMissingConfiguration() : string;

    /**
     * @return string
     */
    public function getComponentName() : string;
}