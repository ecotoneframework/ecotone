<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Endpoint\Interceptor;

/**
 * Global termination signal service that maintains a single termination flag
 * and handles signal registration for SIGINT, SIGTERM, and SIGQUIT.
 *
 * licence Apache-2.0
 */
class PcntlTerminationListener implements TerminationListener
{
    private bool $terminationRequested = false;
    private bool $enabled = false;

    /**
     * @var array<int, callable|int|string>
     */
    private array $originalHandlers = [];

    public function __destruct()
    {
        $this->disable();
    }

    /**
     * Enable signal handling by registering handlers for termination signals.
     * If already enabled, resets the termination flag.
     */
    public function enable(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        if ($this->enabled) {
            $this->terminationRequested = false;
            return;
        }

        $this->enabled = true;
        $this->terminationRequested = false;

        // Store original handlers
        foreach ([SIGINT, SIGTERM, SIGQUIT] as $signal) {
            $this->originalHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, fn (int $signal) => $this->terminationRequested = true);
        }

        // Enable async signals
        pcntl_async_signals(true);
    }

    /**
     * Disable signal handling by restoring original handlers.
     */
    public function disable(): void
    {
        if (! extension_loaded('pcntl')) {
            return;
        }

        if (! $this->enabled) {
            return;
        }

        // Restore original handlers
        foreach ($this->originalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }

        $this->originalHandlers = [];
        $this->enabled = false;
        $this->terminationRequested = false;
    }

    /**
     * Check if termination was requested.
     */
    public function shouldTerminate(): bool
    {
        return $this->terminationRequested;
    }

    /**
     * Manually reset the termination flag without disabling handlers.
     */
    public function reset(): void
    {
        $this->terminationRequested = false;
    }
}
