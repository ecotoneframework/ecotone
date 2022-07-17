<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Class LoggerExample
 * @package Test\Ecotone\Messaging\Unit\Handler\Logger
 * @author Dariusz Gafka <dgafka.mail@gmail.com>
 */
class LoggerExample implements LoggerInterface
{
    private array $emergency = [];
    private array $alert = [];
    private array $critical = [];
    private array $error = [];
    private array $warning = [];
    private array $notice = [];
    private array $info = [];
    private array $debug = [];
    private array $log = [];

    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }

    /**
     * @return array
     */
    public function getEmergency(): array
    {
        return $this->emergency;
    }

    /**
     * @return array
     */
    public function getAlert(): array
    {
        return $this->alert;
    }

    /**
     * @return array
     */
    public function getCritical(): array
    {
        return $this->critical;
    }

    /**
     * @return array
     */
    public function getError(): array
    {
        return $this->error;
    }

    /**
     * @return array
     */
    public function getWarning(): array
    {
        return $this->warning;
    }

    /**
     * @return array
     */
    public function getNotice(): array
    {
        return $this->notice;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * @return array
     */
    public function getDebug(): array
    {
        return $this->debug;
    }

    /**
     * @return array
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * @inheritDoc
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->emergency[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->alert[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->critical[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->error[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->warning[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->notice[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->info[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->debug[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->log[] = $level;
    }
}
