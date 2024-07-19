<?php

declare(strict_types=1);

namespace Ecotone\Messaging\Handler\Recoverability;

use Ecotone\Messaging\Config\Container\DefinedObject;
use Ecotone\Messaging\Config\Container\Definition;
use Ecotone\Messaging\Support\Assert;

/**
 * licence Apache-2.0
 */
final class RetryTemplateBuilder implements DefinedObject
{
    /**
     * @var int in milliseconds
     */
    private int $initialDelay;
    private int $multiplier;
    private ?int $maxDelay;
    private ?int $maxAttempts;

    public function __construct(int $initialDelay, int $multiplier, ?int $maxDelay, ?int $maxAttempts)
    {
        Assert::isTrue($maxAttempts > 0 || is_null($maxAttempts), 'Max attempts must be greater than 0');
        Assert::isTrue($maxDelay > 0 || is_null($maxDelay), 'Max delay must be greater than 0');
        Assert::isTrue($multiplier > 0, 'Multiplier must be greater than 0');
        Assert::isTrue($initialDelay > 0, 'Initial delay must be greater than 0');

        $this->initialDelay = $initialDelay;
        $this->multiplier = $multiplier;
        $this->maxDelay = $maxDelay;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * Perform each retry after fixed amount of time
     */
    public static function fixedBackOff(int $initialDelay): self
    {
        return new self($initialDelay, 1, null, null);
    }

    public static function exponentialBackoff(int $initialDelay, int $multiplier): self
    {
        return new self($initialDelay, $multiplier, null, null);
    }

    public static function exponentialBackoffWithMaxDelay(int $initialDelay, int $multiplier, int $maxDelay): self
    {
        return new self($initialDelay, $multiplier, $maxDelay, null);
    }

    public function maxRetryAttempts(int $maxAttempts): self
    {
        return new self($this->initialDelay, $this->multiplier, $this->maxDelay, $maxAttempts);
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
