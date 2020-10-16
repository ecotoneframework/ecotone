<?php
declare(strict_types=1);


namespace Ecotone\Messaging\Endpoint\Interceptor;

use Ecotone\Messaging\Config\ConfigurationException;
use Ecotone\Messaging\Endpoint\ConsumerInterceptor;

/**
 * Class SignalInterceptor
 * @package Ecotone\Messaging\Endpoint\Interceptor
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class SignalInterceptor implements ConsumerInterceptor
{
    /** @var int supervisor default stop */
    private const SIGNAL_TERMINATE = 15;
    /** @var int kill -s */
    private const SIGNAL_QUIT = 3;
    /** @var int ctrl+c */
    private const SIGNAL_INTERRUPT = 2;

    private bool $shouldBeStopped = false;

    /**
     * @inheritDoc
     */
    public function onStartup(): void
    {
        if (!extension_loaded('pcntl')) {
            throw ConfigurationException::create("pcntl extension need to be loaded in order to catch system signals");
        }

        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'stopConsumer']);
        pcntl_signal(SIGQUIT, [$this, 'stopConsumer']);
        pcntl_signal(SIGINT, [$this, 'stopConsumer']);
    }

    /**
     * @inheritDoc
     */
    public function shouldBeStopped(): bool
    {
        return $this->shouldBeStopped;
    }

    /**
     * @inheritDoc
     */
    public function preRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function postRun(): void
    {
    }

    /**
     * @inheritDoc
     */
    public function shouldBeThrown(\Throwable $exception) : bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function postSend(): void
    {
    }

    /**
     * @param int $signal
     */
    public function stopConsumer(int $signal): void
    {
        if (in_array($signal, [self::SIGNAL_INTERRUPT, self::SIGNAL_QUIT, self::SIGNAL_TERMINATE])) {
            $this->shouldBeStopped = true;
        }
    }
}