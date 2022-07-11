<?php

namespace Ecotone\Amqp\Configuration;

use Enqueue\AmqpExt\AmqpConnectionFactory;

class AmqpConfiguration
{
    public const DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS = true;
    public const DEFAULT_TRANSACTION_ON_COMMAND_BUS            = true;
    public const DEFAULT_TRANSACTION_ON_CONSOLE_COMMANDS = true;

    private bool $transactionOnAsynchronousEndpoints = self::DEFAULT_TRANSACTION_ON_ASYNCHRONOUS_ENDPOINTS;
    private bool $transactionOnCommandBus = self::DEFAULT_TRANSACTION_ON_COMMAND_BUS;
    private bool $transactionOnConsoleCommands = self::DEFAULT_TRANSACTION_ON_CONSOLE_COMMANDS;


    private array $defaultConnectionReferenceNames = [];

    private function __construct()
    {
    }

    public static function createWithDefaults(): self
    {
        return new self();
    }

    public function withTransactionOnAsynchronousEndpoints(bool $isTransactionEnabled): self
    {
        $self                                     = clone $this;
        $self->transactionOnAsynchronousEndpoints = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnCommandBus(bool $isTransactionEnabled): self
    {
        $self                          = clone $this;
        $self->transactionOnCommandBus = $isTransactionEnabled;

        return $self;
    }

    public function withTransactionOnConsoleCommands(bool $isTransactionEnabled): self
    {
        $self                          = clone $this;
        $self->transactionOnConsoleCommands = $isTransactionEnabled;

        return $self;
    }

    public function withDefaultConnectionReferenceNames(array $connectionReferenceNames = [AmqpConnectionFactory::class]): self
    {
        $self = clone $this;
        $self->defaultConnectionReferenceNames = $connectionReferenceNames;

        return $self;
    }

    public function isTransactionOnAsynchronousEndpoints(): bool
    {
        return $this->transactionOnAsynchronousEndpoints;
    }

    public function isTransactionOnCommandBus(): bool
    {
        return $this->transactionOnCommandBus;
    }

    public function isTransactionOnConsoleCommands(): bool
    {
        return $this->transactionOnConsoleCommands;
    }

    public function getDefaultConnectionReferenceNames(): array
    {
        return $this->defaultConnectionReferenceNames;
    }
}
