<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

/**
 * licence Apache-2.0
 */
final class PushStatistics
{
    public function __construct(private string $id)
    {

    }

    public function getId(): string
    {
        return $this->id;
    }
}
