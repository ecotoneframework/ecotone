<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

/**
 * Interface Lifecycle
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ConsumerLifecycle
{
    /**
     * start component
     */
    public function run(): void;

    /**
     * stop component from running
     */
    public function stop(): void;

    /**
     * @return bool
     */
    public function isRunningInSeparateThread(): bool;
}
