<?php

namespace Ecotone\Modelling\AggregateFlow\LoadAggregate;

/**
 * licence Apache-2.0
 */
class LoadAggregateMode
{
    private const THROW_ON_NOT_FOUND = 1;
    private const DROP_ON_NOT_FOUND = 2;
    private const CONTINUE_ON_NOT_FOUND = 3;

    private int $type;

    public function __construct(int $type)
    {
        $this->type = $type;
    }

    public static function createThrowOnNotFound(): self
    {
        return new self(self::THROW_ON_NOT_FOUND);
    }

    public static function createDropMessageOnNotFound(): self
    {
        return new self(self::DROP_ON_NOT_FOUND);
    }

    public static function createContinueOnNotFound(): self
    {
        return new self(self::CONTINUE_ON_NOT_FOUND);
    }

    public function isThrowingOnNotFound(): bool
    {
        return $this->type === self::THROW_ON_NOT_FOUND;
    }

    public function isDroppingMessageOnNotFound(): bool
    {
        return $this->type === self::DROP_ON_NOT_FOUND;
    }

    public function getType(): int
    {
        return $this->type;
    }
}
