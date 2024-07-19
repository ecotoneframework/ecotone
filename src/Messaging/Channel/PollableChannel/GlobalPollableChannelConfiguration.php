<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Channel\PollableChannel;

use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplateBuilder;

/**
 * licence Apache-2.0
 */
final class GlobalPollableChannelConfiguration
{
    private function __construct(
        private RetryTemplate $retryTemplate,
        private bool          $collectorEnabled = true,
        private ?string $errorChannelName = null
    ) {
    }

    public static function createWithDefaults(): self
    {
        return new self(self::defaultRetry());
    }

    public static function create(RetryTemplate $retryTemplate): self
    {
        return new self($retryTemplate);
    }

    public static function neverRetry(): self
    {
        return new self(RetryTemplate::createNeverRetry());
    }

    public function withCollector(bool $collectorEnabled): self
    {
        $self = clone $this;
        $self->collectorEnabled = $collectorEnabled;

        return $self;
    }

    public function withErrorChannel(string $errorChannelName): self
    {
        $self = clone $this;
        $self->errorChannelName = $errorChannelName;

        return $self;
    }

    private static function defaultRetry(): RetryTemplate
    {
        return RetryTemplateBuilder::exponentialBackoff(1, 20)
            ->maxRetryAttempts(2)
            ->build();
    }

    public function getRetryTemplate(): RetryTemplate
    {
        return $this->retryTemplate;
    }

    public function isCollectorEnabled(): bool
    {
        return $this->collectorEnabled;
    }

    public function getErrorChannelName(): ?string
    {
        return $this->errorChannelName;
    }
}
