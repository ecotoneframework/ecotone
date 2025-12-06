<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint;

use Throwable;

/**
 * Interface ConsumerExtension
 * @package Ecotone\Messaging\Endpoint
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
interface ConsumerInterceptor
{
    /**
     * Do some one time action before this consumer will start running
     */
    public function onStartup(): void;

    /**
     * Do some one time action when the consumer is shutting down
     */
    public function onShutdown(): void;

    /**
     * should this consumer be stopped before next run
     */
    public function shouldBeStopped(): bool;

    /**
     * handle exception
     */
    public function shouldBeThrown(Throwable $exception): bool;

    /**
     *  Called before each run
     */
    public function preRun(): void;

    /**
     * Called after each run
     */
    public function postRun(?Throwable $unhandledFailure): void;

    /**
     * Called after each sending message to request channel
     */
    public function postSend(): void;
}
