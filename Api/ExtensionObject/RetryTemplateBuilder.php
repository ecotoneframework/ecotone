<?php

declare(strict_types=1);

namespace Ecotone\Api\ExtensionObject;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Handler\Recoverability\RetryTemplate;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class RetryTemplateBuilder implements DefinedObject
{
    private int $initialDelay;
    private int $multiplier;
    private ?int $maxDelay;
    private ?int $maxAttempts;

    public function __construct(int $initialDelay, int $multiplier, ?int $maxDelay, ?int $maxAttempts)
    {
        Assert::isTrue($maxAttempts > 0 || is_null($maxAttempts), "Retry max retries must be greater than 0, got {$maxAttempts}");
        Assert::isTrue($maxDelay > 0 || is_null($maxDelay), 'Max delay must be greater than 0');
        Assert::isTrue($multiplier > 0, 'Multiplier must be greater than 0');
        Assert::isTrue($initialDelay >= 0, "Retry initial delay must be 0 or greater, got {$initialDelay} ms");

        $this->initialDelay = $initialDelay;
        $this->multiplier = $multiplier;
        $this->maxDelay = $maxDelay;
        $this->maxAttempts = $maxAttempts;
    }

    public static function fixedBackOff(int $delayInMilliseconds): self
    {
        return new self($delayInMilliseconds, 1, null, null);
    }

    public static function exponentialBackOff(int $initialDelayInMilliseconds, int $multiplier): self
    {
        return new self($initialDelayInMilliseconds, $multiplier, null, null);
    }

    public static function exponentialBackOffWithMaxDelay(int $initialDelayInMilliseconds, int $multiplier, int $maxDelayInMilliseconds): self
    {
        return new self($initialDelayInMilliseconds, $multiplier, $maxDelayInMilliseconds, null);
    }

    public function maxRetries(int $maxRetries): self
    {
        return new self($this->initialDelay, $this->multiplier, $this->maxDelay, $maxRetries);
    }

    public function build(): RetryTemplate
    {
        return new RetryTemplate($this->initialDelay, $this->multiplier, $this->maxDelay, $this->maxAttempts);
    }

    public function getDefinition(): Definition
    {
        return new Definition(
            self::class,
            [
                $this->initialDelay,
                $this->multiplier,
                $this->maxDelay,
                $this->maxAttempts,
            ]
        );
    }
}
