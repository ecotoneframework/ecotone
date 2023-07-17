<?php

declare(strict_types=1);

namespace Test\Ecotone\Modelling\Fixture\OrderAsynchronousEventHandler;

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
