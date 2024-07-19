<?php

namespace Test\Ecotone\Messaging\Fixture\Conversion;

/**
 * licence Apache-2.0
 */
class InCorrectArrayDocblock
{
    /**
     * @var rabbitMq[]
     */
    private array $incorrectProperty;

    /**
     * @param rabbitMq[] $data
     */
    public function incorrectParameter(array $data): void
    {
    }

    /**
     * @return rabbitMq[]
     */
    public function incorrectReturnType(): array
    {
    }
}
