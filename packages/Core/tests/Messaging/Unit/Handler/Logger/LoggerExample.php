<?php

namespace Test\Ecotone\Messaging\Unit\Handler\Logger;

use Psr\Log\LoggerInterface;

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
    public function emergency($message, array $context = [])
    {
        $this->emergency[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function alert($message, array $context = [])
    {
        $this->alert[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function critical($message, array $context = [])
    {
        $this->critical[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function error($message, array $context = [])
    {
        $this->error[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function warning($message, array $context = [])
    {
        $this->warning[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function notice($message, array $context = [])
    {
        $this->notice[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function info($message, array $context = [])
    {
        $this->info[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function debug($message, array $context = [])
    {
        $this->debug[] = $message;
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        $this->log[] = $level;
    }
}
