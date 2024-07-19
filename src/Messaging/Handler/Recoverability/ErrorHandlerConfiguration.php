<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Recoverability;

/**
 * licence Apache-2.0
 */
class ErrorHandlerConfiguration
{
    private string $errorChannelName;
    private RetryTemplate $delayedRetryTemplate;
    private ?string $deadLetterChannel;

    private function __construct(string $errorChannelName, RetryTemplate $delayedRetryTemplate, ?string $deadLetterChannel)
    {
        $this->deadLetterChannel    = $deadLetterChannel;
        $this->delayedRetryTemplate = $delayedRetryTemplate;
        $this->errorChannelName     = $errorChannelName;
    }

    public static function create(string $errorChannelName, RetryTemplateBuilder $delayedRetryTemplate): self
    {
        return new self($errorChannelName, $delayedRetryTemplate->build(), null);
    }

    public static function createWithDeadLetterChannel(string $errorChannelName, RetryTemplateBuilder $delayedRetryTemplate, string $deadLetterChannel): self
    {
        return new self($errorChannelName, $delayedRetryTemplate->build(), $deadLetterChannel);
    }

    public static function createDefault(): self
    {
        return ErrorHandlerConfiguration::createWithDeadLetterChannel(
            'errorChannel',
            RetryTemplateBuilder::exponentialBackoff(1000, 10)
                ->maxRetryAttempts(3),
            'dbal_dead_letter'
        );
    }

    /**
     * @return string
     */
    public function getErrorChannelName(): string
    {
        return $this->errorChannelName;
    }

    public function getDeadLetterQueueChannel(): ?string
    {
        return $this->deadLetterChannel;
    }

    public function getDelayedRetryTemplate(): RetryTemplate
    {
        return $this->delayedRetryTemplate;
    }
}
