<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;
use Ecotone\Messaging\Endpoint\ConsumerInterceptorTrait;

/**
 * Class SignalInterceptor
 * @package Ecotone\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <support@simplycodedsoftware.com>
 */
/**
 * licence Apache-2.0
 */
class SignalInterceptor implements ConsumerInterceptor
{
    use ConsumerInterceptorTrait;
    private bool $shouldBeStopped = false;
    private ?bool $pcntlAsyncSignalsOriginalState = null;
    /**
     * @var array<int, callable|int>|null
     */
    private ?array $beforeHandlers = null;

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        if (! extension_loaded('pcntl')) {
            throw ConfigurationException::create('pcntl extension need to be loaded in order to catch system signals');
        }

        $this->pcntlAsyncSignalsOriginalState = pcntl_async_signals();
        pcntl_async_signals(true);

        $this->beforeHandlers = [
            SIGTERM => pcntl_signal_get_handler(SIGTERM),
            SIGQUIT => pcntl_signal_get_handler(SIGQUIT),
            SIGINT => pcntl_signal_get_handler(SIGINT),
        ];

        pcntl_signal(SIGTERM, $this->stopConsumer(...));
        pcntl_signal(SIGQUIT, $this->stopConsumer(...));
        pcntl_signal(SIGINT, $this->stopConsumer(...));
    }

    public function onShutdown(): void
    {
        if ($this->beforeHandlers !== null) {
            foreach ($this->beforeHandlers as $signal => $handler) {
                pcntl_signal($signal, $handler);
            }
            $this->beforeHandlers = null;
        }

        if ($this->pcntlAsyncSignalsOriginalState !== null) {
            pcntl_async_signals($this->pcntlAsyncSignalsOriginalState);
            $this->pcntlAsyncSignalsOriginalState = null;
        }
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return $this->shouldBeStopped;
    }

    /**
     * @param int $signal
     */
    public function stopConsumer(int $signal): void
    {
        $this->shouldBeStopped = true;
    }
}
